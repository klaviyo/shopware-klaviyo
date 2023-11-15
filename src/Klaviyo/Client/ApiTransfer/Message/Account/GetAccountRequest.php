<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account;

class GetAccountRequest
{
    public function __construct(
        private readonly string $accountId
    ) {
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }
}
