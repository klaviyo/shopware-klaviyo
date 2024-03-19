<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;

class GetListProfilesResponse
{
    private bool $success;
    private ProfileInfoCollection $profiles;
    private ?int $lastRequestCursorMarker;
    private ?string $errorDetails;

    public function __construct(
        bool $success,
        ProfileInfoCollection $profiles,
        ?int $lastRequestCursorMarker = null,
        ?string $errorDetails = null
    ) {
        $this->success = $success;
        $this->profiles = $profiles;
        $this->lastRequestCursorMarker = $lastRequestCursorMarker;
        $this->errorDetails = $errorDetails;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getProfiles(): ProfileInfoCollection
    {
        return $this->profiles;
    }

    public function getLastRequestCursorMarker(): ?int
    {
        return $this->lastRequestCursorMarker;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }
}
