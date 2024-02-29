<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common;

class ProfileContactInfo
{
    private string $customerId;
    private ?string $email;
    private ?string $firstname;
    private ?string $lastname;
    private ?string $salutation;

    public function __construct(
        string $customerId,
        string $email = null,
        string $firstname = null,
        string $lastname = null,
        string $salutation = null
    ) {
        $this->customerId = $customerId;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->salutation = $salutation;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }
}
