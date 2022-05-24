<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message\Migration;

use Klaviyo\Integration\Async\Message\AbstractBasicMessage;
use Klaviyo\Integration\Model\UseCase\Operation\Migration\FullProfileExternalIdSyncOperation;

class FullProfileExternalIdSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Full Klaviyo Profile Data Migration';

    public function getHandlerCode(): string
    {
        return FullProfileExternalIdSyncOperation::OPERATION_HANDLER_CODE;
    }
}
