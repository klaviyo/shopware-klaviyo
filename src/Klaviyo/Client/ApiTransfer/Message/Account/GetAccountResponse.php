<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account;

class GetAccountResponse
{
    private bool $success;
    private string $accountId;
    private string $code;
    private string $errorDetails;

    public function __construct(
        bool $success,
        string $accountId,
        string $code = '',
        string $errorDetails = ''
    ) {
        $this->success = $success;
        $this->accountId = $accountId;
        $this->code = $code;
        $this->errorDetails = $errorDetails;
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
