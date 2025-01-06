<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\CustomOptions;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;

class CartCustomOptionsResult
{
    private array $customOptions;
    private LineItem $mainLineItem;

    public function __construct(
        LineItem $mainLineItem,
        ?array $customOptions
    )
    {
        $this->mainLineItem = $mainLineItem;
        $this->customOptions = $customOptions;
    }

    public function getCustomOptions(): array
    {
        return $this->customOptions;
    }

    public function getMainLineItem(): LineItem
    {
        return $this->mainLineItem;
    }
}
