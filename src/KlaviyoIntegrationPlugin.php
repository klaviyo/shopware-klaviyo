<?php

namespace Klaviyo\Integration;

use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Connection;
use Klaviyo\Integration\Utils\MigrationHelper;
use Od\Scheduler\OdScheduler;
use Shopware\Core\Framework\Parameter\AdditionalBundleParameters;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
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

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_event`');
        $connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_cart_request`');
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
//            new OdScheduler(true, $this->getBasePath() . '/vendor/od/sw6-job-scheduler')
            new OdScheduler()
        ];
    }
}
