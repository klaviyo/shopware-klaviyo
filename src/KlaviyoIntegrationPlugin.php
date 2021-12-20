<?php

namespace Klaviyo\Integration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class KlaviyoIntegrationPlugin extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $dropPagesTableSQL = 'DROP TABLE IF EXISTS `klaviyo_job`';
        $connection->executeStatement($dropPagesTableSQL);
    }
}
