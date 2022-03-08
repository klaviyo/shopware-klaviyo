<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common;

class CustomerProperties implements \JsonSerializable
{
    private string $email;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $birthday;
    private ?string $phone_number;
    private ?string $address;
    private ?string $city;
    private ?string $zip;
    private ?string $region;
    private ?string $country;
    private array $customFields;

    public function __construct(
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $birthday = null,
        ?string $phone_number = null,
        ?string $address = null,
        ?string $city = null,
        ?string $zip = null,
        ?string $region = null,
        ?string $country = null,
        array $customFields = []
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthday = $birthday;
        $this->phone_number = $phone_number;
        $this->address = $address;
        $this->city = $city;
        $this->zip = $zip;
        $this->region = $region;
        $this->country = $country;
        $this->customFields = $customFields;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
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

    public function jsonSerialize()
    {
        $basicData = [
            'email' => $this->getEmail(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'birthday' => $this->getBirthday(),
            'phoneNumber' => $this->getPhoneNumber(),
            'city' => $this->getCity(),
            'zip' => $this->getZip(),
            'address' => $this->getAddress(),
            'region' => $this->getRegion(),
            'country' => $this->getCountry(),
        ];

        foreach ($this->getCustomFields() as $fieldKey => $fieldValue) {
            $basicData[$fieldKey] = $fieldValue;
        }

        return $basicData;
    }
}
