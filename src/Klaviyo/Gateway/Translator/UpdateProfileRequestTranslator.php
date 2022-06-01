<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileRequest;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

class UpdateProfileRequestTranslator
{
    private CustomerPropertiesTranslator $customerPropertiesTranslator;

    public function __construct(CustomerPropertiesTranslator $customerPropertiesTranslator)
    {
        $this->customerPropertiesTranslator = $customerPropertiesTranslator;
    }

    public function translateToProfileRequest(Context $context, CustomerEntity $customer, string $profileId)
    {
        $customerProperties = $this->customerPropertiesTranslator->translateCustomer($context, $customer);

        return new UpdateProfileRequest($profileId, $customerProperties);
    }
}
