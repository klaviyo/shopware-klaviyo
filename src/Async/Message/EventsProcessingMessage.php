<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\EventsProcessingOperation;

class EventsProcessingMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Scheduled Events Sync';

    public function getHandlerCode(): string
    {
        return EventsProcessingOperation::HANDLER_CODE;
    }
}
