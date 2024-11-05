<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Async\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ScheduleFullCustomerSubsSyncTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.job.full_customer_subs_sync_processing';
    }

    /**
     * 8 min interval
     *
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 60 * 8;
    }
}
