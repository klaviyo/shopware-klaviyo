<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway\Translator;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\AddressDataHelper;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Gateway\Exception\TranslationException;
use Klaviyo\Integration\Utils\LocaleCodeProducer;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CustomerPropertiesTranslator
{
    private AddressDataHelper $addressHelper;
    private ConfigurationRegistry $configurationRegistry;
    private EntityRepository $salesChannelRepository;
    private LocaleCodeProducer $localeCodeProducer;
    private EntityRepository $customerGroupRepository;
    private EntityRepository $customerRepository;

    public function __construct(
        AddressDataHelper $addressHelper,
        ConfigurationRegistry $configurationRegistry,
        EntityRepository $salesChannelRepository,
        LocaleCodeProducer $localeCodeProducer,
        EntityRepository $customerGroupRepository,
        EntityRepository $customerRepository
    ) {
        $this->addressHelper = $addressHelper;
        $this->configurationRegistry = $configurationRegistry;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->localeCodeProducer = $localeCodeProducer;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Context $context
     * @param OrderEntity $orderEntity
     *
     * @return CustomerProperties
     * @throws JobRuntimeWarningException
     */
    public function translateOrder(Context $context, OrderEntity $orderEntity): CustomerProperties
    {
        $configuration = $this->configurationRegistry->getConfiguration($orderEntity->getSalesChannelId());
        $orderCustomer = $orderEntity->getOrderCustomer();

        if (!$orderCustomer) {
            throw new TranslationException('OrderEntity translation failed, because order customer is empty');
        }

        $customer = $orderCustomer->getCustomer();

        if (null === $customer && !$configuration->isTrackDeletedAccountOrders()) {
            throw new JobRuntimeWarningException(
                \sprintf('Order[id: %s] associated account has been deleted - skipping.', $orderEntity->getId())
            );
        }

        $customerAddress = $this->guessRelevantCustomerAddress($customer);

        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);

        $customerCustomFields = $this->prepareCustomerCustomFields($customer, $orderEntity->getSalesChannelId());
        $birthday = $customer?->getBirthday();
        $title = $customer ? $customer->getTitle() : null;
        $accountType = $customer ? $customer->getAccountType() : null;
        $company = $customer ? $customer->getCompany() : null;
        $vatId = $customer && $customer->getVatIds() ? implode(', ', $customer->getVatIds()) : null;
        $additionalAddressLine1 = $customerAddress ? $customerAddress->getAdditionalAddressLine1() : null;
        $additionalAddressLine2 = $customerAddress ? $customerAddress->getAdditionalAddressLine2() : null;
        $customerNumber = $customer ? $customer->getCustomerNumber() : null;
        $customerId = $customer ? $customer->getId() : null;
        $affiliateCode = $customer ? $customer->getAffiliateCode() : null;
        $campaignCode = $customer ? $customer->getCampaignCode() : null;

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
            $customerCustomFields,
            $birthday ? $birthday->format(Defaults::STORAGE_DATE_FORMAT) : null,
            $customer ? $customer->getSalesChannelId() : null,
            $customer ? $this->getSalesChannelName(
                $customer->getSalesChannelId(),
                $customer->getSalesChannel(),
                $context
            ) : null,
            $customer ? $customer->getBoundSalesChannelId() : null,
            $customer ? $this->getSalesChannelName(
                $customer->getBoundSalesChannelId(),
                $customer->getBoundSalesChannel(),
                $context
            ) : null,
            $this->getLocaleCode($orderEntity, $context),
            $this->getCustomerGroupName($customer, $context),
            $title,
            $accountType,
            $company,
            $vatId,
            $additionalAddressLine1,
            $additionalAddressLine2,
            $customerNumber,
            $customerId,
            $affiliateCode,
            $campaignCode
        );
    }

    /**
     * Get locale code.
     *
     * @param OrderEntity $orderEntity
     * @param Context $context
     *
     * @return string|null
     */
    private function getLocaleCode(OrderEntity $orderEntity, Context $context): ?string
    {
        try {
            $orderCustomer = $orderEntity->getOrderCustomer()->getCustomer();

            return $this->localeCodeProducer->getLocaleCodeFromContext(
                $orderCustomer ? $orderCustomer->getLanguageId() : $orderEntity->getLanguageId(),
                $context
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getCustomerGroupName(?CustomerEntity $customer, Context $context): ?string
    {
        if (!$customer) {
            return null;
        }

        $groupId = $customer->getGroupId();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $groupId));
        $group = $this->customerGroupRepository->search($criteria, $context)->first();

        return $group->getTranslation('name');
    }

    private function getCustomerAddressAssociation(?CustomerEntity $customer, Context $context): ?CustomerEntity
    {
        if (!$customer) {
            return null;
        }

        $criteria = new Criteria([$customer->getId()]);
        $criteria->addAssociation('defaultBillingAddress');
        $criteria->addAssociation('defaultShippingAddress');

        return $this->customerRepository->search($criteria, $context)->first();
    }

    private function guessRelevantCustomerAddress(?CustomerEntity $customerEntity): ?CustomerAddressEntity
    {
        $customerEntity = $this->getCustomerAddressAssociation($customerEntity, Context::createDefaultContext());

        if (!$customerEntity) {
            return null;
        }

        if ($customerEntity->getActiveBillingAddress()) {
            return $customerEntity->getActiveBillingAddress();
        }

        return $customerEntity->getActiveShippingAddress();
    }

    private function prepareCustomerCustomFields(?CustomerEntity $customer, string $channelId): array
    {
        if (null === $customer) {
            return [];
        }

        $configuration = $this->configurationRegistry->getConfiguration($channelId);
        $fieldMapping = $configuration->getCustomerCustomFieldMapping();
        $customFields = [];

        foreach ($customer->getCustomFields() ?? [] as $fieldName => $fieldValue) {
            if (isset($fieldMapping[$fieldName])) {
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

        if (!$customerEntity->getActiveBillingAddress() && !$customerEntity->getActiveShippingAddress()) {
            $customerEntity = $this->getCustomerAddressAssociation($customerEntity, Context::createDefaultContext());
        }

        if (!$customerEntity) {
            return null;
        }

        $address = $customerEntity->getActiveBillingAddress();
        if ($address && $address->getPhoneNumber()) {
            $phoneNumber = $address->getPhoneNumber();

            if ($this->phoneValidationE164($phoneNumber)) {
                return $address->getPhoneNumber();
            }
        }

        $address = $customerEntity->getActiveShippingAddress();

        if ($address && $address->getPhoneNumber()) {
            $phoneNumber = $address->getPhoneNumber();

            if ($this->phoneValidationE164($phoneNumber)) {
                return $address->getPhoneNumber();
            }
        }

        return null;
    }

    private function phoneValidationE164(string $phoneNumber): bool
    {
        $phoneNumber = str_replace(' ', '', $phoneNumber);
        $result = preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber);

        if (1 !== $result) {
            return false;
        }

        return true;
    }

    protected function getSalesChannelName(?string $id, ?SalesChannelEntity $channelEntity, Context $context): ?string
    {
        if ($channelEntity) {
            return $channelEntity->getName();
        }
        if (!$id) {
            return null;
        }
        $criteria = new Criteria([$id]);
        $loadedChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        return $loadedChannel ? $loadedChannel->getName() : null;
    }

    public function translateCustomer(Context $context, CustomerEntity $customerEntity): CustomerProperties
    {
        $customerAddress = $this->guessRelevantCustomerAddress($customerEntity);
        $state = $this->addressHelper->getAddressRegion($context, $customerAddress);
        $country = $this->addressHelper->getAddressCountry($context, $customerAddress);
        $birthday = $customerEntity->getBirthday();
        $customFields = $this->prepareCustomerCustomFields($customerEntity, $customerEntity->getSalesChannelId());
        $localeCode = $this->localeCodeProducer->getLocaleCodeFromContext($customerEntity->getLanguageId(), $context);
        $title = $customerEntity->getTitle();
        $accountType = $customerEntity->getAccountType();
        $company = $customerEntity->getCompany();
        $vatId = $customerEntity->getVatIds() ? implode(', ', $customerEntity->getVatIds()) : null;
        $additionalAddressLine1 = $customerAddress?->getAdditionalAddressLine1();
        $additionalAddressLine2 = $customerAddress?->getAdditionalAddressLine2();
        $customerNumber = $customerEntity->getCustomerNumber();
        $customerId = $customerEntity->getId();
        $affiliateCode = $customerEntity->getAffiliateCode();
        $campaignCode = $customerEntity->getCampaignCode();

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
            $birthday ? $birthday->format(Defaults::STORAGE_DATE_FORMAT) : null,
            $customerEntity->getSalesChannelId(),
            $this->getSalesChannelName(
                $customerEntity->getSalesChannelId(),
                $customerEntity->getSalesChannel(),
                $context
            ),
            $customerEntity->getBoundSalesChannelId(),
            $this->getSalesChannelName(
                $customerEntity->getBoundSalesChannelId(),
                $customerEntity->getBoundSalesChannel(),
                $context
            ),
            $localeCode ?: null,
            $this->getCustomerGroupName($customerEntity, $context),
            $title,
            $accountType,
            $company,
            $vatId,
            $additionalAddressLine1,
            $additionalAddressLine2,
            $customerNumber,
            $customerId,
            $affiliateCode,
            $campaignCode
        );
    }
}
