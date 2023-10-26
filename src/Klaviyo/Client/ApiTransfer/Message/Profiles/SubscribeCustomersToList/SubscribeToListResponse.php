<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;

class SubscribeToListResponse
{
    private bool $success;
    private ProfileInfoCollection $addedProfiles;
    private string $errorDetails;

    public function __construct(
        bool $success,
        ProfileInfoCollection $addedProfiles,
        string $errorDetails = ''
    ) {
        $this->success = $success;
        $this->addedProfiles = $addedProfiles;
        $this->errorDetails = $errorDetails;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getAddedProfiles(): ProfileInfoCollection
    {
        return $this->addedProfiles;
    }

    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }
}
