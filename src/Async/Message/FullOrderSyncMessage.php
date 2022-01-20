<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\FullOrderSyncOperation;

class FullOrderSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Full Order Sync Operation';

    public function getHandlerCode(): string
    {
        return FullOrderSyncOperation::OPERATION_HANDLER_CODE;
    }
}
