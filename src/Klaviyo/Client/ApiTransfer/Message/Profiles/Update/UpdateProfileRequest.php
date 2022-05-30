<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;

class UpdateProfileRequest
{
    private string $profileId;
    private CustomerProperties $customerProperties;

    public function __construct(
        string $profileId,
        CustomerProperties $customerProperties
    ) {
        $this->profileId = $profileId;
        $this->customerProperties = $customerProperties;
    }

    public function getCustomerProperties(): CustomerProperties
    {
        return $this->customerProperties;
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }
}
