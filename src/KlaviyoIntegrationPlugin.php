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
        $connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_event`');
        $connection->executeStatement('DROP TABLE IF EXISTS `klaviyo_job_cart_request`');
    }
}
