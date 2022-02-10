<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;

class IdentifyProfileRequest
{
    private CustomerProperties $customerProperties;

    public function __construct(CustomerProperties $customerProperties)
    {
        $this->customerProperties = $customerProperties;
    }

    public function getCustomerProperties(): CustomerProperties
    {
        return $this->customerProperties;
    }
}
