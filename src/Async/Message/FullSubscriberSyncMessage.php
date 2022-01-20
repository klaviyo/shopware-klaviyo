<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\FullSubscriberSyncOperation;

class FullSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Full Subscriber Sync Operation';

    public function getHandlerCode(): string
    {
        return FullSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }
}
