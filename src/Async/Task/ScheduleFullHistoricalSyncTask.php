<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ScheduleFullHistoricalSyncTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.job.full_historical_sync_processing';
    }

    /**
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 60 * 11;
    }
}
