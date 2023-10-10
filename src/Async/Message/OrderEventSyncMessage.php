<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\OrderEventsSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;
use Shopware\Core\Framework\Context;

class OrderEventSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    protected static string $defaultName = 'Order Events Sync Operation';
    private array $eventIds;
    private string $parentJobId;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $eventIds,
        ?Context $context,
        ?string $name = null
    ) {
        parent::__construct($jobId, $name, $context);
        $this->eventIds = $eventIds;
        $this->parentJobId = $parentJobId;
    }

    public function getEventIds(): array
    {
        return $this->eventIds;
    }

    public function getHandlerCode(): string
    {
        return OrderEventsSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }
}
