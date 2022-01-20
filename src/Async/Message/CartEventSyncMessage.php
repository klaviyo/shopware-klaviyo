<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\CartEventSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;

class CartEventSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    private array $eventRequestIds;
    private string $parentJobId;
    protected static string $defaultName = 'Cart Events Sync Operation';

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $eventRequestIds,
        ?string $name = null
    ) {
        parent::__construct($jobId, $name);
        $this->eventRequestIds = $eventRequestIds;
        $this->parentJobId = $parentJobId;
    }

    public function getEventRequestIds(): array
    {
        return $this->eventRequestIds;
    }

    public function getHandlerCode(): string
    {
        return CartEventSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }
}
