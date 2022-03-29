<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\ExcludedSubscriberSyncOperation;

class ExcludedSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Excluded Subscriber Sync Operation';

    public function getHandlerCode(): string
    {
        return ExcludedSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }
}