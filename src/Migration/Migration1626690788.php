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
        CREATE TABLE `klaviyo_job` (
            `id` BINARY(16) NOT NULL,
            `status` VARCHAR(255) NOT NULL,
            `type` VARCHAR(255) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT '0',
            `created_by_schedule` TINYINT(1) NOT NULL DEFAULT '0',
            `started_at` DATETIME(3) NULL,
            `finished_at` DATETIME(3) NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
            ALTER TABLE `klaviyo_job`
                ADD INDEX `klaviyo_job_type_idx` (`type` ASC),
                ADD INDEX `klaviyo_job_active_type_created_dt_idx` (`active` ASC, `type` ASC, `created_at` DESC),
                ADD INDEX `klaviyo_job_status_type_fin_dt_idx` (`type` ASC, `status` ASC, `finished_at` DESC),
                ADD INDEX `klaviyo_job_type_created_dt_idx` (`type` ASC, `created_at` DESC),
                ADD INDEX `klaviyo_job_fin_dt_idx` (`finished_at` DESC),
                ADD INDEX `klaviyo_job_created_by_schedule_idx` (`created_by_schedule` DESC),
                ADD INDEX `klaviyo_job_status_idx` (`status` ASC);
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
