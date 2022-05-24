<?php declare(strict_types=1);

namespace Klaviyo\Integration\Async\Message\Migration;

use Klaviyo\Integration\Async\Message\AbstractBasicMessage;

class ProfileExternalIdSyncMessage extends AbstractBasicMessage
{
    protected static string $defaultName = 'Existing Profile Data Migration';
    private string $channelId;
    private array $customerEmails;

    public function __construct(
        string $jobId,
        string $channelId,
        array $customerEmails
    ) {
        parent::__construct($jobId);
        $this->channelId = $channelId;
        $this->customerEmails = $customerEmails;
    }

    public function getCustomerEmails(): array
    {
        return $this->customerEmails;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getHandlerCode(): string
    {
        // TODO: Implement getHandlerCode() method.
    }
}
