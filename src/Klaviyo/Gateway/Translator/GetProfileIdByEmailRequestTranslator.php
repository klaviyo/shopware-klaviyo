<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdByEmailRequest;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class GetProfileIdByEmailRequestTranslator
{
    public function translateToGetProfileIdRequest(CustomerEntity $customer)
    {
        return new GetProfileIdByEmailRequest('', $customer);
    }
}
