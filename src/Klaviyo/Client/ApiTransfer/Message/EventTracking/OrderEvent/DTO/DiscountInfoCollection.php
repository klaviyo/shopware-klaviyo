<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO;

use Shopware\Core\Framework\Struct\Collection;

class DiscountInfoCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return DiscountInfo::class;
    }
}