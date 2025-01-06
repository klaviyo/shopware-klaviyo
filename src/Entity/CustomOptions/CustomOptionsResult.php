<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CustomOptions;

class CustomOptionsResult
{
    private array $customOptionsProductsData;
    private array $customOptions;

    public function __construct(
        ?array $customOptions,
        ?array $customOptionsProductsData
    )
    {
        $this->customOptionsProductsData = $customOptionsProductsData;
        $this->customOptions = $customOptions;
    }

    public function getCustomOptions(): array
    {
        return $this->customOptions;
    }

    public function getCustomOptionsProductsData(): array
    {
        return $this->customOptionsProductsData;
    }
}
