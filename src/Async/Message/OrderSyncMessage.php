<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\OrderSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;
use Shopware\Core\Framework\Context;

class OrderSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    protected static string $defaultName = 'Order Sync Operation';
    private string $parentJobId;
    private array $orderIds;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $orderIds,
        ?string $name = null,
        ?Context $context = null
    ) {
        parent::__construct($jobId, $name, $context);
        $this->parentJobId = $parentJobId;
        $this->orderIds = $orderIds;
    }

    public function getHandlerCode(): string
    {
        return OrderSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }

    public function getOrderIds(): array
    {
        return $this->orderIds;
    }
}
