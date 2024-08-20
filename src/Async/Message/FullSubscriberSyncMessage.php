<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\FullSubscriberSyncOperation;
use Shopware\Core\Framework\Context;

class FullSubscriberSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Full Subscriber Sync Operation';

    private int $offset;

    public function __construct(
        string   $jobId,
        ?string  $name = null,
        ?Context $context = null,
        int      $offset = 0
    )
    {
        parent::__construct($jobId, $name, $context);
        $this->offset = $offset;
    }
    public function getHandlerCode(): string
    {
        return FullSubscriberSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
