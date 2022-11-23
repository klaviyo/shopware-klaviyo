<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\SubscriberSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;
use Shopware\Core\Framework\Context;

class SubscriberSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    protected static string $defaultName = 'Subscriber Sync Operation';
    private array $subscriberIds;
    private string $parentJobId;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $subscriberIds,
        ?string $name = null,
        ?Context $context = null
    ) {
        parent::__construct($jobId, $name, $context);
        $this->subscriberIds = $subscriberIds;
        $this->parentJobId = $parentJobId;
    }

    public function getHandlerCode(): string
    {
        return SubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getSubscriberIds(): array
    {
        return $this->subscriberIds;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }
}
