<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search;

use Shopware\Core\Checkout\Customer\CustomerEntity;

class GetProfileIdByEmailRequest
{
    private CustomerEntity $customerEntity;

    public function __construct(
        CustomerEntity $customerEntity
    ) {
        $this->customerEntity = $customerEntity;
    }

    public function getSearchFieldName(): string
    {
        return 'email';
    }

    public function getFieldValue(): CustomerEntity
    {
        return $this->customerEntity;
    }
}
