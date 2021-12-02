<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

use Shopware\Core\Framework\Struct\Collection;

class CheckoutLineItemInfoCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return CheckoutLineItemInfo::class;
    }
}