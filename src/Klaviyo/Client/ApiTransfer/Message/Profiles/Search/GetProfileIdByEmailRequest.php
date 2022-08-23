<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search;

use Shopware\Core\Checkout\Customer\CustomerEntity;

class GetProfileIdByEmailRequest implements GetProfileIdRequestInterface
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
        return 'email';
    }

    public function getSearchFieldValue(): string
    {
        return $this->customerEntity->getEmail();
    }
}
