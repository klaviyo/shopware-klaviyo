<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO\CartProductInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;

class AddedToCartEventTrackingRequest extends EventTrackingRequest
{
    private float $cartTotal;
    private float $addedItemPrice;
    private string $addedItemProductName;
    private string $addedItemProductId;
    private string $addedItemProductSKU;
    private array $addedItemCategoryNames;
    private string $addedItemImageUrl;
    private string $addedItemUrl;
    private int $addedItemQty;
    private string $checkoutURL;
    private CartProductInfoCollection $cartProductInfoCollection;

    public function __construct(
        string $eventId,
        \DateTimeInterface $time,
        ?CustomerProperties $customerProperties,
        float $cartTotal,
        float $addedItemPrice,
        string $addedItemProductName,
        string $addedItemProductId,
        string $addedItemProductSKU,
        array $addedItemCategoryNames,
        string $addedItemImageUrl,
        string $addedItemUrl,
        int $addedItemQty,
        string $checkoutURL,
        CartProductInfoCollection $cartProductInfoCollection
    ) {
        parent::__construct($eventId, $time, $customerProperties);

        $this->cartTotal = $cartTotal;
        $this->addedItemPrice = $addedItemPrice;
        $this->addedItemProductName = $addedItemProductName;
        $this->addedItemProductId = $addedItemProductId;
        $this->addedItemProductSKU = $addedItemProductSKU;
        $this->addedItemCategoryNames = $addedItemCategoryNames;
        $this->addedItemImageUrl = $addedItemImageUrl;
        $this->addedItemUrl = $addedItemUrl;
        $this->addedItemQty = $addedItemQty;
        $this->checkoutURL = $checkoutURL;
        $this->cartProductInfoCollection = $cartProductInfoCollection;
    }

    public function getCartTotal(): float
    {
        return $this->cartTotal;
    }

    public function getAddedItemPrice(): float
    {
        return $this->addedItemPrice;
    }

    public function getAddedItemProductName(): string
    {
        return $this->addedItemProductName;
    }

    public function getAddedItemProductId(): string
    {
        return $this->addedItemProductId;
    }

    public function getAddedItemProductSKU(): string
    {
        return $this->addedItemProductSKU;
    }

    public function getAddedItemCategoryNames(): array
    {
        return $this->addedItemCategoryNames;
    }

    public function getAddedItemImageUrl(): string
    {
        return $this->addedItemImageUrl;
    }

    public function getAddedItemUrl(): string
    {
        return $this->addedItemUrl;
    }

    public function getAddedItemQty(): int
    {
        return $this->addedItemQty;
    }

    public function getCheckoutURL(): string
    {
        return $this->checkoutURL;
    }

    public function getCartProductInfoCollection(): CartProductInfoCollection
    {
        return $this->cartProductInfoCollection;
    }
}