<?php
declare(strict_types=1);

namespace Klaviyo\Integration\System;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

readonly class ConfigService
{
    public function __construct(
        private Connection $connection
    ){}

    /**
     * @throws Exception
     */
    public function getConfigValueWithoutCache(string $configKey, ?string $salesChannelId = null) : ?int
    {
        $sql = 'SELECT configuration_value
                FROM system_config
                WHERE configuration_key = :configKey
                AND (sales_channel_id = :salesChannelId OR sales_channel_id IS NULL)
                ORDER BY sales_channel_id DESC
                LIMIT 1';

        $result = $this->connection->fetchOne($sql, [
            'configKey' => $configKey,
            'salesChannelId' => $salesChannelId
        ]);

        if (!empty($result)) {
            $value = json_decode($result, true);
            return array_shift($value);
        }

        return null;
    }
}
