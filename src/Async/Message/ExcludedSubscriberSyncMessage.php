<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\ExcludedSubscriberSyncOperation;

class ExcludedSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Excluded Subscriber Sync Operation';
    private string $parentJobId;
    private array $emails;

    public function __construct(
        string $jobId,
        string $parentJobId,
        array $emails,
        ?string $name = null
    ) {
        parent::__construct($jobId, $name);
        $this->parentJobId = $parentJobId;
        $this->emails = $emails;
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
}