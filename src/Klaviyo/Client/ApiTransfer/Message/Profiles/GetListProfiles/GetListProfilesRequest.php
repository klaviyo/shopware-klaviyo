<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles;

class GetListProfilesRequest
{
    private string $listId;
    private ?string $cursorMarker;

    public function __construct(string $listId, ?string $cursorMarker)
    {
        $this->listId = $listId;
        $this->cursorMarker = $cursorMarker;
    }

    public function getListId(): string
    {
        return $this->listId;
    }

    public function getCursorMarker(): ?string
    {
        return $this->cursorMarker;
    }
}