<?php

namespace Klaviyo\Integration\Storefront\Checkout\Cart\RestorerService;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface RestorerServiceInterface
{
    public function restore(string $mappingId, SalesChannelContext $context): void;
}
