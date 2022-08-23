<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdRequestInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;

interface GetProfileIdByFieldRequestTranslatorInterface
{
    public function translateToGetProfileIdRequest(CustomerEntity $customer): GetProfileIdRequestInterface;
}
