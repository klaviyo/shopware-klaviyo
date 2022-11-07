<?php declare(strict_types=1);

namespace Klaviyo\Integration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1626690788EventTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1626690788;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `klaviyo_job_event` (
            `id`                BINARY(16)      NOT NULL,
            `type`              VARCHAR(255)    NOT NULL,
            `entity_id`         BINARY(16)      NOT NULL,
            `sales_channel_id`  BINARY(16)      NOT NULL,
            `happened_at`       DATETIME(3)     NOT NULL,
            `created_at`        DATETIME(3)     NOT NULL,
            `updated_at`        DATETIME(3)     NULL,
            PRIMARY KEY (`id`),
            INDEX `odklin_klaviyo_job_event_type_idx` (`type`),
            INDEX `odklin_klaviyo_job_event_created_at_idx` (`created_at`),
            CONSTRAINT `fk.klaviyo_job_event.sales_channel_id`
                FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `klaviyo_job_cart_request` (
            `id`                    BINARY(16)      NOT NULL,
            `sales_channel_id`      BINARY(16)      NOT NULL,
            `serialized_request`    LONGTEXT        NOT NULL,
            `created_at`            DATETIME(3)     NOT NULL,
            `updated_at`            DATETIME(3)     NULL,
            PRIMARY KEY (`id`),
            INDEX `odklin_klaviyo_job_cart_request_created_at_idx` (`created_at`),
            CONSTRAINT `fk.klaviyo_job_cart_request.sales_channel_id`
                FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
