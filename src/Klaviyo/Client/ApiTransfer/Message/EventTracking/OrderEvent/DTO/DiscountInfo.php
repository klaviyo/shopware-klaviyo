<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO;

class DiscountInfo
{
    private string $code;
    private float $value;

    public function __construct(string $code, float $value)
    {
        $this->code = $code;
        $this->value = $value;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getValue(): float
    {
        return $this->value;
    }
}