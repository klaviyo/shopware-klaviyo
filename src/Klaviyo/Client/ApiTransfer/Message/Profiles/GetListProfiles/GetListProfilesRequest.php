<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles;

class GetListProfilesRequest
{
    private string $listId;
    private ?string $nextPageUrl;

    public function __construct(string $listId, ?string $nextPageUrl)
    {
        $this->listId = $listId;
        $this->nextPageUrl = $nextPageUrl;
    }

    public function getListId(): string
    {
        return $this->listId;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function setNextPageUrl(string $nextPageUrl): GetListProfilesRequest
    {
        $this->nextPageUrl = $nextPageUrl;

        return $this;
    }
}
