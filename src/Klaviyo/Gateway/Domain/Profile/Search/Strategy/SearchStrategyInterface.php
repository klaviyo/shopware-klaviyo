<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\Strategy;

use Klaviyo\Integration\Klaviyo\Gateway\Domain\Profile\Search\ProfileIdSearchResult;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;

interface SearchStrategyInterface
{
    public function searchProfilesIds(
        Context $context,
        string $channelId,
        CustomerCollection $customers
    ): ProfileIdSearchResult;
}
