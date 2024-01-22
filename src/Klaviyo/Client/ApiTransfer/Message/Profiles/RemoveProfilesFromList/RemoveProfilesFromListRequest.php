<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfoCollection;

class RemoveProfilesFromListRequest
{
    private string $listId;
    private ProfileContactInfoCollection $profiles;

    public function __construct(string $listId, ProfileContactInfoCollection $profiles)
    {
        $this->listId = $listId;
        $this->profiles = $profiles;
    }

    public function getListId(): string
    {
        return $this->listId;
    }

    public function getProfiles(): ProfileContactInfoCollection
    {
        return $this->profiles;
    }
}
