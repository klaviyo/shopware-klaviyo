<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdByEmailRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdRequestInterface;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class GetProfileIdByEmailRequestTranslator implements GetProfileIdByFieldRequestTranslatorInterface
{
    public function translateToGetProfileIdRequest(CustomerEntity $customer): GetProfileIdRequestInterface
    {
        return new GetProfileIdByEmailRequest($customer);
    }
}
