<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class CustomerProperties implements \JsonSerializable
{
    private string $email;
    private ?string $id;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $phone_number;
    private ?string $address;
    private ?string $city;
    private ?string $zip;
    private ?string $region;
    private ?string $country;
    private array $customFields;
    private ?string $birthday;
    private ?string $salesChannelId;
    private ?string $salesChannelName;
    private ?string $boundedSalesChannelId;
    private ?string $boundedSalesChannelName;
    private ?string $localeCode;
    private ?string $customerGroup;
    private ?string $title;
    private ?string $accountType;
    private ?string $company;
    private ?string $vatId;
    private ?string $additionalAddressLine1;
    private ?string $additionalAddressLine2;
    private ?string $customerNumber;
    private ?string $customerId;
    private ?string $affiliateCode;
    private ?string $campaignCode;

    public function __construct(
        string $email,
        ?string $id,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone_number = null,
        ?string $address = null,
        ?string $city = null,
        ?string $zip = null,
        ?string $region = null,
        ?string $country = null,
        array $customFields = [],
        ?string $birthday = null,
        ?string $salesChannelId = null,
        ?string $salesChannelName = null,
        ?string $boundedSalesChannelId = null,
        ?string $boundedSalesChannelName = null,
        ?string $localeCode = null,
        ?string $customerGroup = null,
        ?string $title = null,
        ?string $accountType = null,
        ?string $company = null,
        ?string $vatId = null,
        ?string $additionalAddressLine1 = null,
        ?string $additionalAddressLine2 = null,
        ?string $customerNumber = null,
        ?string $customerId = null,
        ?string $affiliateCode = null,
        ?string $campaignCode = null
    ) {
        $this->email = $email;
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone_number = $phone_number;
        $this->address = $address;
        $this->city = $city;
        $this->zip = $zip;
        $this->region = $region;
        $this->country = $country;
        $this->customFields = $customFields;
        $this->birthday = $birthday;
        $this->salesChannelId = $salesChannelId;
        $this->salesChannelName = $salesChannelName;
        $this->boundedSalesChannelId = $boundedSalesChannelId;
        $this->boundedSalesChannelName = $boundedSalesChannelName;
        $this->localeCode = $localeCode;
        $this->customerGroup = $customerGroup;
        $this->title = $title;
        $this->accountType = $accountType;
        $this->company = $company;
        $this->vatId = $vatId;
        $this->additionalAddressLine1 = $additionalAddressLine1;
        $this->additionalAddressLine2 = $additionalAddressLine2;
        $this->customerNumber = $customerNumber;
        $this->customerId = $customerId;
        $this->affiliateCode = $affiliateCode;
        $this->campaignCode = $campaignCode;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): ?string
    {
        return $this->phone_number = $phone_number;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function getSalesChannelName(): ?string
    {
        return $this->salesChannelName;
    }

    public function getBoundedSalesChannelId(): ?string
    {
        return $this->boundedSalesChannelId;
    }

    public function getBoundedSalesChannelName(): ?string
    {
        return $this->boundedSalesChannelName;
    }

    public function jsonSerialize()
    {
        $basicData = [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'phoneNumber' => $this->getPhoneNumber(),
            'city' => $this->getCity(),
            'zip' => $this->getZip(),
            'address' => $this->getAddress(),
            'region' => $this->getRegion(),
            'country' => $this->getCountry(),
            'birthday' => $this->getBirthday(),
            'salesChannelId' => $this->getSalesChannelId(),
            'salesChannelName' => $this->getSalesChannelName(),
            'boundedSalesChannelId' => $this->getBoundedSalesChannelId(),
            'boundedSalesChannelName' => $this->getBoundedSalesChannelName(),
            'language' => $this->getLocaleCode(),
            'customerGroup' => $this->getGroupName(),
            'title' => $this->getTitle(),
            'accountType' => $this->getAccountType(),
            'company' => $this->getCompany(),
            'vatId' => $this->getVatId(),
            'additionalAddress' => $this->getAdditionalAddress(),
            'customerNumber' => $this->getCustomerNumber(),
            'customerId' => $this->getCustomerId(),
            'affiliateCode' => $this->getAffiliateCode(),
            'campaignCode' => $this->getCampaignCode(),
        ];

        foreach ($this->getCustomFields() as $fieldKey => $fieldValue) {
            $basicData[$fieldKey] = $fieldValue;
        }

        return $basicData;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function getGroupName(): ?string
    {
        return $this->customerGroup;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAccountType(): ?string
    {
        return $this->accountType;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function getAdditionalAddressLine1(): ?string
    {
        return $this->additionalAddressLine1;
    }

    public function getAdditionalAddressLine2(): ?string
    {
        return $this->additionalAddressLine2;
    }

    public function getAdditionalAddress(): ?string
    {
        $addressParts = array_filter([
            $this->getAdditionalAddressLine1(),
            $this->getAdditionalAddressLine2()
        ]);

        return empty($addressParts) ? null : implode(' ', $addressParts);
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getAffiliateCode(): ?string
    {
        return $this->affiliateCode;
    }

    public function getCampaignCode(): ?string
    {
        return $this->campaignCode;
    }
}
