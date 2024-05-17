<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists;

class GetProfilesListsRequest
{
    private ?string $nextPageUrl;
    private ?string $listId;

    public function __construct(string $nextPageUrl = null, string $listId = null)
    {
        $this->nextPageUrl = $nextPageUrl;
        $this->listId = $listId;
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

    public function getListId(): ?string
    {
        return $this->listId;
    }
}
