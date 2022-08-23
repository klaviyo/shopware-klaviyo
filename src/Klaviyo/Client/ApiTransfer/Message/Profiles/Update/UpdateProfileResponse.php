<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update;

class UpdateProfileResponse
{
    private bool $isSuccess;
    private ?string $errorDetail;

    public function __construct(
        bool $isSuccess,
        ?string $errorDetail = null
    ) {
        $this->isSuccess = $isSuccess;
        $this->errorDetail = $errorDetail;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getErrorDetail(): ?string
    {
        return $this->errorDetail;
    }
}
