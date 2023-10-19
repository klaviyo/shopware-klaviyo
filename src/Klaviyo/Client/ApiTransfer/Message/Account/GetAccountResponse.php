<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account;

class GetAccountResponse
{
    public function __construct(
        private readonly bool $success,
        private readonly string $accountId,
        private readonly string $code = '',
        private readonly string $errorDetails = ''
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getAccountIdFromKlaviyo(): string
    {
        return $this->accountId;
    }

    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
