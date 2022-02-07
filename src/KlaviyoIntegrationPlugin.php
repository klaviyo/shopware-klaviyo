<?php

namespace Klaviyo\Integration;

use Doctrine\DBAL\Connection;
use Klaviyo\Integration\Utils\Lifecycle;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class KlaviyoIntegrationPlugin extends Plugin
{

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

}