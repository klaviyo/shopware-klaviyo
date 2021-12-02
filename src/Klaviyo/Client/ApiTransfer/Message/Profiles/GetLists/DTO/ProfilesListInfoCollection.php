<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO;

use Shopware\Core\Framework\Struct\Collection;

class ProfilesListInfoCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return ProfilesListInfo::class;
    }
}