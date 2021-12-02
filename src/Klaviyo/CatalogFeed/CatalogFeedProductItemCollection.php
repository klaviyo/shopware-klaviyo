<?php

namespace Klaviyo\Integration\Klaviyo\CatalogFeed;

use Shopware\Core\Framework\Struct\Collection;

class CatalogFeedProductItemCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return CatalogFeedProductItemInfo::class;
    }
}