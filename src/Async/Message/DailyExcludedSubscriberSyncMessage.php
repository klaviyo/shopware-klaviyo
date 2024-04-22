<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\DailyEventProcessExcludedSubscriberSyncOperation;

class DailyExcludedSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Excluded Subscribers Daily Sync';

    public function getHandlerCode(): string
    {
        return DailyEventProcessExcludedSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }
}
