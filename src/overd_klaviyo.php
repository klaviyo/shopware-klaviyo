<?php

namespace Klaviyo\Integration;

use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Connection;
use Klaviyo\Integration\Utils\{Lifecycle, Lifecycle\Update\UpdateOldTemplate, Lifecycle\Update\UpdateTo105, MigrationHelper};
use League\Flysystem\{Adapter\Local, Filesystem};
use Od\Scheduler\OdScheduler;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\{ActivateContext, UninstallContext, UpdateContext};
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class overd_klaviyo extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        /** @var AssetService $assetService */
        $assetService = $this->container->get('klaviyo.plugin.assetservice.public');
        /** @var MigrationHelper $migrationHelper */
        $migrationHelper = $this->container->get(MigrationHelper::class);

        foreach ($this->getDependencyBundles() as $bundle) {
            $migrationHelper->getMigrationCollection($bundle)->migrateInPlace();
            $assetService->copyAssetsFromBundle((new \ReflectionClass($bundle))->getShortName());
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), "1.0.5", '<=')) {
            (new UpdateTo105(
                $this->container->get(SystemConfigService::class),
                $this->container->get('sales_channel.repository')
            ))->execute($updateContext->getContext());
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), "1.0.6", '<=')) {
            $adapter = new Local(__DIR__);
            $filesystem = new Filesystem($adapter);
            $connection = $this->container->get(Connection::class);
            (new UpdateOldTemplate($filesystem, $connection))->updateTemplateByMD5hash();
        }

        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $hasOtherSchedulerDependency = false;
        $bundleParameters = new AdditionalBundleParameters(new ClassLoader(), new Plugin\KernelPluginCollection(), []);
        $kernel = $this->container->get('kernel');

        foreach ($kernel->getPluginLoader()->getPluginInstances()->getActives() as $bundle) {
            if (!$bundle instanceof Plugin || $bundle instanceof self) {
                continue;
            }

            $schedulerDependencies = \array_filter(
                $bundle->getAdditionalBundles($bundleParameters),
                function (BundleInterface $bundle) {
                    return $bundle instanceof OdScheduler;
                }
            );

            if (\count($schedulerDependencies) !== 0) {
                $hasOtherSchedulerDependency = true;
                break;
            }
        }

        (new Lifecycle($this->container, $hasOtherSchedulerDependency))->uninstall($uninstallContext);
    }

    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        self::classLoader();

        return $this->getDependencyBundles();
    }

    public static function classLoader(): void
    {
        $file = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($file)) {
            return;
        }

        /** @noinspection UsingInclusionOnceReturnValueInspection */
        $classLoader = require_once $file;

        if (!$classLoader instanceof ClassLoader) {
            return;
        }

        $classLoader->unregister();
        $classLoader->register(false);
    }

    private function getDependencyBundles(): array
    {
        return [
            new OdScheduler()
        ];
    }
}
