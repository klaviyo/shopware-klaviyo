<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common;

class ProfileInfo extends ProfileContactInfo
{
    private string $id;

    public function __construct(string $id, string $email)
    {
        $this->id = $id;

        parent::__construct($email);
    }

    public function getId(): string
    {
        return $this->id;
    }
}