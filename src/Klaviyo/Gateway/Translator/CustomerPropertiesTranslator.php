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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Klaviyo\Integration\Utils\LocaleCodeProducer;

class CustomerPropertiesTranslator
{
    private AddressDataHelper $addressHelper;
    private ConfigurationRegistry $configurationRegistry;
    private EntityRepositoryInterface $salesChannelRepository;
    private LocaleCodeProducer $localeCodeProducer;

    public function __construct(
        AddressDataHelper $addressHelper,
        ConfigurationRegistry $configurationRegistry,
        EntityRepositoryInterface $salesChannelRepository,
        LocaleCodeProducer $localeCodeProducer
    ) {
        $this->addressHelper = $addressHelper;
        $this->configurationRegistry = $configurationRegistry;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->localeCodeProducer = $localeCodeProducer;
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
        $birthday = $customer ? $customer->getBirthday() : null;

        $localeCode = $this->localeCodeProducer->getLocaleCodeFromContext($customer->getLanguageId(), $context);

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
            $customFields,
            $birthday ? $birthday->format(Defaults::STORAGE_DATE_FORMAT) : null,
            $customer ? $customer->getSalesChannelId() : null,
            $customer ? $this->getSalesChannelName($customer->getSalesChannelId(), $customer->getSalesChannel(), $context) : null,
            $customer ? $customer->getBoundSalesChannelId(): null,
            $customer ? $this->getSalesChannelName($customer->getBoundSalesChannelId(), $customer->getBoundSalesChannel(), $context) : null,
            $localeCode ?: null
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
            $phoneNumber = $address->getPhoneNumber();
            // Format phone number to E.164
            return $this->fixForE164($phoneNumber);
        }

        $address = $customerEntity->getActiveShippingAddress();
        if ($address && $address->getPhoneNumber()) {
            $phoneNumber = $address->getPhoneNumber();
            // Format phone number to E.164
            return $this->fixForE164($phoneNumber);
        }

        return null;
    }

    private function fixForE164($e164_phone): ?string
    {
        $e164_phone = str_replace(' ', '', $e164_phone);

        if (strlen($e164_phone) > 15) {
          $e164_phone = substr($e164_phone, 0, 10) . preg_replace('/[\/a-z-]0.+$/', '', substr($e164_phone, 10));
        }

        $e164_phone = preg_replace('/[^0-9]/', '', $e164_phone);

        // Add a plus sign if missing
        if (substr($e164_phone, 0, 1) !== '+') {
            $e164_phone = '+' . $e164_phone;
        }

        // Ensure minimum length and validity
        if (strlen($e164_phone) < 8 || !preg_match('/^\+[1-9][0-9]+$/', $e164_phone)) {
            return null;
        }

        if (substr($e164_phone, 0, 1) !== '+' || strlen($e164_phone) < 8) {
          $e164_phone = null;
        }

        return substr($e164_phone, 0, 16);
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
        $customFields = $this->prepareCustomFields($customerEntity, $customerEntity->getSalesChannelId());
        $localeCode = $this->localeCodeProducer->getLocaleCodeFromContext($customerEntity->getLanguageId(), $context);

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
            $this->getSalesChannelName($customerEntity->getSalesChannelId(), $customerEntity->getSalesChannel(), $context),
            $customerEntity->getBoundSalesChannelId(),
            $this->getSalesChannelName($customerEntity->getBoundSalesChannelId(), $customerEntity->getBoundSalesChannel(), $context),
            $localeCode ?: null
        );
    }
}
