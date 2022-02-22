<?php declare(strict_types=1);

namespace Od\Scheduler\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1641974495 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1641974495;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `od_scheduler_job` (
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
            PRIMARY KEY (`id`),
            INDEX `osj_parent_id_idx` (`parent_id`),
            INDEX `osj_parent_status_idx` (`status`),
            INDEX `osj_parent_type_idx` (`type`),
            CONSTRAINT `fk.od_scheduler_job.parent_id.job_id`
                FOREIGN KEY (`parent_id`)
                REFERENCES `od_scheduler_job` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `od_scheduler_job_message` (
            `id`            BINARY(16)      NOT NULL,
            `job_id`        BINARY(16)      NOT NULL,
            `type`          VARCHAR(255)    NOT NULL,
            `message`       LONGTEXT        NOT NULL,
            `created_at`    DATETIME(3)     NOT NULL,
            `updated_at`    DATETIME(3)     NULL,
            PRIMARY KEY (`id`),
            INDEX `osjm_job_id_type_idx` (`job_id`, `type`),
            INDEX `osjm_created_at_idx` (`created_at`),
            CONSTRAINT `fk.od_scheduler_job_message.job_id`
                FOREIGN KEY (`job_id`)
                REFERENCES `od_scheduler_job` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
