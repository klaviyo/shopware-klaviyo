<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common;

class ProfileContactInfo
{
    private string $customerId;
    private ?string $email;

    public function __construct(string $customerId, string $email = null)
    {
        $this->customerId = $customerId;
        $this->email = $email;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
