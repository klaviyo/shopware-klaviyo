<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO;

use Shopware\Core\Framework\Struct\Collection;

class CartProductInfoCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return CartProductInfo::class;
    }
}