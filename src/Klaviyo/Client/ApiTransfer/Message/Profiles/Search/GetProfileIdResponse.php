<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search;

class GetProfileIdResponse
{
    private bool $isSuccess;
    private ?string $profileId;
    private ?string $errorDetail;

    public function __construct(
        bool $isSuccess,
        string $profileId = null,
        string $errorDetail = null
    ) {
        $this->isSuccess = $isSuccess;
        $this->profileId = $profileId;
        $this->errorDetail = $errorDetail;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getProfileId(): ?string
    {
        return $this->profileId;
    }

    public function getErrorDetail(): ?string
    {
        return $this->errorDetail;
    }
}
