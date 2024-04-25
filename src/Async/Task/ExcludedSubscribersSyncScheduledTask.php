<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class ExcludedSubscribersSyncScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.job.excluded_subscribers_sync.task';
    }

    public static function getDefaultInterval(): int
    {
        return 86400;
    }
}
