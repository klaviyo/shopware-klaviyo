<?php declare(strict_types=1);

namespace Klaviyo\Integration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1626690788 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1626690788;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `klaviyo_job` (
            `id`            BINARY(16)      NOT NULL,
            `parent_id`     BINARY(16)      NULL,
            `status`        VARCHAR(255)    NOT NULL,
            `type`          VARCHAR(255)    NOT NULL,
            `name`          VARCHAR(255)    NOT NULL,
            `message`       LONGTEXT        NULL,
            `started_at`    DATETIME(3)     NULL,
            `finished_at`   DATETIME(3)     NULL,
            `created_at`    DATETIME(3)     NOT NULL,
            `updated_at`    DATETIME(3)     NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `klaviyo_job_message` (
            `id`            BINARY(16)      NOT NULL,
            `job_id`        BINARY(16)      NOT NULL,
            `message`       LONGTEXT        NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.klaviyo_job_message.job_id`
                FOREIGN KEY (`job_id`)
                REFERENCES `klaviyo_job` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `klaviyo_job_event` (
            `id`                BINARY(16)      NOT NULL,
            `type`              VARCHAR(255)    NOT NULL,
            `entity_id`         BINARY(16)      NOT NULL,
            `sales_channel_id`  BINARY(16)      NOT NULL,
            `happened_at`       DATETIME(3)     NOT NULL,
            `created_at`        DATETIME(3)     NOT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk.klaviyo_job_event.sales_channel_id`
                FOREIGN KEY (`sales_channel_id`)
                REFERENCES `sales_channel` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);

        //TODO: add indices
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
