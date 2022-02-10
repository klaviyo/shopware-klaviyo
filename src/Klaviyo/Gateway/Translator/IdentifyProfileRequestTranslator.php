<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify\IdentifyProfileRequest;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

class IdentifyProfileRequestTranslator
{
    private CustomerPropertiesTranslator $customerPropertiesTranslator;

    public function __construct(CustomerPropertiesTranslator $customerPropertiesTranslator)
    {
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
    }

    public function translateToProfileRequest(Context $context, CustomerEntity $customer): IdentifyProfileRequest
    {
        $customerProperties = $this->customerPropertiesTranslator->translateCustomer($context, $customer);

        return new IdentifyProfileRequest($customerProperties);
    }
}
