<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ScheduleEventJobsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.tracking_integration.schedule_events_processing';
    }

    /**
     * TODO: what interval ????
     * 5 min interval
     *
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 300;
    }
}
