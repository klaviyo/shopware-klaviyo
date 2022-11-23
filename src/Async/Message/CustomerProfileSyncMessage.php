<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\CustomerProfileSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;
use Shopware\Core\Framework\Context;

class CustomerProfileSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    protected static string $defaultName = 'Customer Profile Sync Operation';
    private string $parentJobId;
    private array $customerIds;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $customerIds,
        ?string $name = null,
        ?Context $context = null
    ) {
        parent::__construct($jobId, $name, $context);
        $this->parentJobId = $parentJobId;
        $this->customerIds = $customerIds;
    }

    public function getHandlerCode(): string
    {
        return CustomerProfileSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }

    public function getCustomerIds(): array
    {
        return $this->customerIds;
    }
}
