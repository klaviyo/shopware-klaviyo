<?php

namespace Klaviyo\Integration\Async;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ClearOldJobsScheduledTask extends ScheduledTask
{
    private const EXECUTION_INTERVAL = '86400';

    public static function getTaskName(): string
    {
        return 'klaviyo.tracking_integration.old_job_records_clear';
    }

    /**
     * Should be the same as the value in the default configuration
     *
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return self::EXECUTION_INTERVAL;
    }
}