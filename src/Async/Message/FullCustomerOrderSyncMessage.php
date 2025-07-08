<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message;

use Klaviyo\Integration\Model\UseCase\Operation\FullCustomerOrderSyncOperation;
use Shopware\Core\Framework\Context;

class FullCustomerOrderSyncMessage extends AbstractDateBasedMessage
{
    protected static string $defaultName = 'Full Customer Sync Operation';

    private int $offset;

    public function __construct(
        string   $jobId,
        ?string  $name = null,
        ?Context $context = null,
        int      $offset = 0,
        ?string   $fromDate = null,
        ?string   $tillDate = null,
    ) {
        parent::__construct($jobId, $name, $context, $fromDate, $tillDate);
        $this->offset = $offset;
    }
    public function getHandlerCode(): string
    {
        return FullCustomerOrderSyncOperation::OPERATION_HANDLER_CODE;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}