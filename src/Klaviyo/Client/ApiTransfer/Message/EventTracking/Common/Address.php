<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class Address
{
    private string $firstName;
    private string $lastName;
    private ?string $company;
    private ?string $street;
    private ?string $street2;
    private string $city;
    private ?string $regionName;
    private ?string $regionCode;
    private ?string $countryName;
    private ?string $countryCode;
    private string $zip;
    private ?string $phone;

    public function __construct(
        string $firstName,
        string $lastName,
        ?string $company,
        ?string $street,
        ?string $street2,
        string $city,
        ?string $regionName,
        ?string $regionCode,
        ?string $countryName,
        ?string $countryCode,
        string $zip,
        ?string $phone
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->company = $company;
        $this->street = $street;
        $this->street2 = $street2;
        $this->city = $city;
        $this->regionName = $regionName;
        $this->regionCode = $regionCode;
        $this->countryName = $countryName;
        $this->countryCode = $countryCode;
        $this->zip = $zip;
        $this->phone = $phone;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}