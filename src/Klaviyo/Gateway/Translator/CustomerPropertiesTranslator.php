<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\AddressDataHelper;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

class CustomerPropertiesTranslator
{
    private AddressDataHelper $addressHelper;
    private ConfigurationRegistry $configurationRegistry;

    public function __construct(
        AddressDataHelper $addressHelper,
        ConfigurationRegistry $configurationRegistry
    ) {
        $this->addressHelper = $addressHelper;
        $this->configurationRegistry = $configurationRegistry;
    }

    public function translateOrder(Context $context, OrderEntity $orderEntity): CustomerProperties
    {
        $configuration = $this->configurationRegistry->getConfiguration($orderEntity->getSalesChannelId());
        $orderCustomer = $orderEntity->getOrderCustomer();
        if (!$orderCustomer) {
            throw new TranslationException(
                'OrderEntity translation failed, because order customer is empty'
            );
        }

        $customer = $orderCustomer->getCustomer();
        if ($customer === null && !$configuration->isTrackDeletedAccountOrders()) {
            throw new JobRuntimeWarningException(
                \sprintf("Order[id: %s] associated account has been deleted - skipping.", $orderEntity->getId())
            );
        }

        $customerAddress = $this->guessRelevantCustomerAddress($customer);

        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);

        $customFields = $this->prepareCustomFields($customer, $orderEntity->getSalesChannelId());

        return new CustomerProperties(
            $customer ? $customer->getEmail() : $orderCustomer->getEmail(),
            $customer ? $customer->getId() : $orderCustomer->getId(),
            $customer ? $customer->getFirstName() : $orderCustomer->getFirstName(),
            $customer ? $customer->getLastName() : $orderCustomer->getLastName(),
            $this->guessRelevantCustomerPhone($customer),
            $customerAddress ? $customerAddress->getStreet() : null,
            $customerAddress ? $customerAddress->getCity() : null,
            $customerAddress ? $customerAddress->getZipcode() : null,
            $state ? $state->getShortCode() : null,
            $country ? $country->getIso() : null,
            $customFields
        );
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

    private function prepareCustomFields(?CustomerEntity $customer, string $channelId): array
    {
        if ($customer === null) {
            return [];
        }

        $configuration = $this->configurationRegistry->getConfiguration($channelId);
        $fieldMapping = $configuration->getCustomerCustomFieldMapping();
        $customFields = [];

        foreach ($customer->getCustomFields() ?? [] as $fieldName => $fieldValue) {
            if (isset($fieldMapping[$fieldName]) && $fieldValue) {
                $customFields[$fieldMapping[$fieldName]] = $fieldValue;
            }
        }

        return $customFields;
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

    public function translateCustomer(Context $context, CustomerEntity $customerEntity): CustomerProperties
    {
        $customerAddress = $this->guessRelevantCustomerAddress($customerEntity);
        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);
        $birthday = $customerEntity->getBirthday();
        $customFields = $this->prepareCustomFields($customerEntity, $customerEntity->getSalesChannelId());

        return new CustomerProperties(
            $customerEntity->getEmail(),
            $customerEntity->getId(),
            $customerEntity->getFirstName(),
            $customerEntity->getLastName(),
            $this->guessRelevantCustomerPhone($customerEntity),
            $customerAddress ? $customerAddress->getStreet() : null,
            $customerAddress ? $customerAddress->getCity() : null,
            $customerAddress ? $customerAddress->getZipcode() : null,
            $state ? $state->getShortCode() : null,
            $country ? $country->getIso() : null,
            $customFields,
            $birthday ? $birthday->format(Defaults::STORAGE_DATE_FORMAT) : null
        );
    }
}
