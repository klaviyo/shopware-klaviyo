<?php

namespace Klaviyo\Integration\Async;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class ClearOldJobsScheduledTaskHandler extends ScheduledTaskHandler
{
    public function run(): void
    {
        //TODO: add clear by interval
    }

    public static function getHandledMessages(): iterable
    {
        return [ClearOldJobsScheduledTask::class];
    }
}
