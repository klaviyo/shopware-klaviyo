<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists;

class GetProfilesListsRequest
{
    private ?string $nextPageUrl;

    public function __construct(string $nextPageUrl = null)
    {
        $this->nextPageUrl = $nextPageUrl;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function setNextPageUrl(string $nextPageUrl): GetProfilesListsRequest
    {
        $this->nextPageUrl = $nextPageUrl;

        return $this;
    }
}
