<?php

namespace Klaviyo\Integration;

use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Connection;
use Klaviyo\Integration\Utils\{Lifecycle, Lifecycle\Update\UpdateTo105, MigrationHelper};
use Od\Scheduler\OdScheduler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\{ActivateContext, UninstallContext, UpdateContext};
use Shopware\Core\Framework\Plugin\Util\AssetService;

class KlaviyoIntegrationPlugin extends Plugin
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
        if (\version_compare($updateContext->getCurrentPluginVersion(), $updateContext->getUpdatePluginVersion(), '<=')) {
            (new UpdateTo105($this->container))->execute($updateContext->getContext());
        }

        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var EntityRepositoryInterface $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        (new Lifecycle($systemConfigRepository, $connection))->uninstall($uninstallContext);
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
