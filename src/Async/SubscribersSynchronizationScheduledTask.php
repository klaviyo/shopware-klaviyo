<?php

namespace Klaviyo\Integration\Async;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SubscribersSynchronizationScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'klaviyo.tracking_integration.subscribers_synchronization';
    }

    /**
     * Should be the same as the value in the default configuration
     *
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 60;
    }
}