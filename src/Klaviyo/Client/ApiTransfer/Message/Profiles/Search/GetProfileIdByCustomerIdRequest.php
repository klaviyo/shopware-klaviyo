<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search;

use Shopware\Core\Checkout\Customer\CustomerEntity;

class GetProfileIdByCustomerIdRequest implements GetProfileIdRequestInterface
{
    private CustomerEntity $customerEntity;

    public function __construct(
        CustomerEntity $customerEntity
    ) {
        $this->customerEntity = $customerEntity;
    }

    public function getCustomer(): CustomerEntity
    {
        return $this->customerEntity;
    }

    public function getSearchFieldName(): string
    {
        return 'external_id';
    }

    public function getSearchFieldValue(): string
    {
        return $this->customerEntity->getId();
    }
}
