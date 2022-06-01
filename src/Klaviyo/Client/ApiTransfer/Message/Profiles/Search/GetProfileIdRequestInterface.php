<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search;

use Shopware\Core\Checkout\Customer\CustomerEntity;

interface GetProfileIdRequestInterface
{
    public function getCustomer(): CustomerEntity;

    public function getSearchFieldName(): string;

    public function getSearchFieldValue(): string;
}
