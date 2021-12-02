<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfoCollection;

class GetProfilesListsResponse
{
    private bool $success;
    private ProfilesListInfoCollection $lists;
    private string $errorDetails;

    public function __construct(bool $success, ProfilesListInfoCollection $lists, string $errorDetails = '')
    {
        $this->success = $success;
        $this->lists = $lists;
        $this->errorDetails = $errorDetails;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getLists(): ProfilesListInfoCollection
    {
        return $this->lists;
    }

    public function getErrorDetails(): string
    {
        return $this->errorDetails;
    }
}