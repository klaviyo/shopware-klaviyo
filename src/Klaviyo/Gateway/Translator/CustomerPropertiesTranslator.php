<?php

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Entity\Helper\AddressDataHelper;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class CustomerPropertiesTranslator
{
    private AddressDataHelper $addressHelper;

    public function __construct(AddressDataHelper $addressHelper)
    {
        $this->addressHelper = $addressHelper;
    }

    public function translateOrder(Context $context, OrderEntity $orderEntity): CustomerProperties
    {
        $orderCustomer = $orderEntity->getOrderCustomer();
        if (!$orderCustomer) {
            throw new TranslationException(
                'OrderEntity translation failed, because order customer is empty'
            );
        }

        $customer = $orderCustomer->getCustomer();

        $customerAddress = $this->guessRelevantCustomerAddress($customer);

        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);

        $customerProperties = new CustomerProperties(
            $orderCustomer->getEmail(),
            $orderCustomer->getFirstName(),
            $orderCustomer->getLastName(),
            $this->guessRelevantCustomerPhone($customer),
            $customerAddress ? $customerAddress->getStreet() : null,
            $customerAddress ? $customerAddress->getCity() : null,
            $customerAddress ? $customerAddress->getZipcode() : null,
            $state ? $state->getShortCode() : null,
            $country ? $country->getIso() : null
        );

        return $customerProperties;
    }

    public function translateCustomer(Context $context, CustomerEntity $customerEntity): CustomerProperties
    {
        $customerAddress = $this->guessRelevantCustomerAddress($customerEntity);

        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);

        $customerProperties = new CustomerProperties(
            $customerEntity->getEmail(),
            $customerEntity->getFirstName(),
            $customerEntity->getLastName(),
            $this->guessRelevantCustomerPhone($customerEntity),
            $customerAddress ? $customerAddress->getStreet() : null,
            $customerAddress ? $customerAddress->getCity() : null,
            $customerAddress ? $customerAddress->getZipcode() : null,
            $state ? $state->getShortCode() : null,
            $country ? $country->getIso() : null
        );

        return $customerProperties;
    }

    private function guessRelevantCustomerPhone(?CustomerEntity $customerEntity): ?string
    {
        if (!$customerEntity) {
            return null;
        }

        $address = $customerEntity->getActiveBillingAddress();
        if ($address && $address->getPhoneNumber()) {
            return $address->getPhoneNumber();
        }

        $address = $customerEntity->getActiveShippingAddress();
        if ($address && $address->getPhoneNumber()) {
            return $address->getPhoneNumber();
        }

        return null;
    }

    private function guessRelevantCustomerAddress(?CustomerEntity $customerEntity): ?CustomerAddressEntity
    {
        if (!$customerEntity) {
            return null;
        }

        if ($customerEntity->getActiveBillingAddress()) {
            return $customerEntity->getActiveBillingAddress();
        }

        return $customerEntity->getActiveShippingAddress();
    }
}