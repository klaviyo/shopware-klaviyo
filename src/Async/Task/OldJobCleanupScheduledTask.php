<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class OldJobCleanupScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.job.cleanup.task';
    }

    /**
     * 1 day interval
     *
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 86400;
    }
}
