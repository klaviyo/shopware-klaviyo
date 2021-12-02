<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO;

use Shopware\Core\Framework\Struct\Collection;

class OrderProductItemInfoCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return OrderProductItemInfo::class;
    }
}