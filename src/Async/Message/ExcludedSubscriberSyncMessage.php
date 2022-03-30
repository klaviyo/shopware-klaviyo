<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\ExcludedSubscriberSyncOperation;

class ExcludedSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Excluded Subscriber Sync Operation';
    private string $parentJobId;
    private string $email;

    public function __construct(
        string $jobId,
        string $parentJobId,
        string $email,
        ?string $name = null
    ) {
        parent::__construct($jobId, $name);
        $this->parentJobId = $parentJobId;
        $this->email = $email;
    }

    public function getHandlerCode(): string
    {
        return ExcludedSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getParentJobId(): string
    {
        return $this->parentJobId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}