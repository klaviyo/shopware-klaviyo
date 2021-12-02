<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common;

use Klaviyo\Integration\Utils\Collection\TypedCollection;

class ProfileInfoCollection extends TypedCollection
{
    public function getItemClassName(): string
    {
        return ProfileInfo::class;
    }
}