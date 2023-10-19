<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account;

class GetAccountRequest
{
    private string $accountId;

    public function __construct(
        string $accountId
    ) {
        $this->accountId = $accountId;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }
}
