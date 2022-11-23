<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\ExcludedSubscriberSyncOperation;
use Od\Scheduler\Async\ParentAwareMessageInterface;
use Shopware\Core\Framework\Context;

class ExcludedSubscriberSyncMessage extends AbstractBasicMessage implements ParentAwareMessageInterface
{
    protected static string $defaultName = 'Excluded Subscriber Sync Operation';
    private string $parentJobId;
    private array $emails;
    private string $salesChannelId;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $emails,
        string $salesChannelId,
        ?string $name = null,
        ?Context $context = null
    ) {
        parent::__construct($jobId, $name, $context);
        $this->parentJobId = $parentJobId;
        $this->emails = $emails;
        $this->salesChannelId = $salesChannelId;
    }

    public function getHandlerCode(): string
    {
        return ExcludedSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }
}
