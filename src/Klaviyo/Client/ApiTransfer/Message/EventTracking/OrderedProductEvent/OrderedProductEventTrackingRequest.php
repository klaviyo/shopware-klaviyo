<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderedProductEvent;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;

class OrderedProductEventTrackingRequest extends EventTrackingRequest
{
    private float $value;
    private string $orderId;
    private string $productId;
    private string $sku;
    private string $productName;
    private int $quantity;
    private string $productURL;
    private string $imageURL;
    private array $categories;
    private string $productBrand;

    public function __construct(
        string $eventId,
        \DateTimeInterface $time,
        ?CustomerProperties $customerProperties,
        float $value,
        string $orderId,
        string $productId,
        string $sku,
        string $productName,
        int $quantity,
        string $productURL,
        string $imageURL,
        array $categories,
        string $productBrand
    ) {
        $this->value = $value;
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->sku = $sku;
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->productURL = $productURL;
        $this->imageURL = $imageURL;
        $this->categories = $categories;
        $this->productBrand = $productBrand;

        parent::__construct($eventId, $time, $customerProperties);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getProductURL(): string
    {
        return $this->productURL;
    }

    public function getImageURL(): string
    {
        return $this->imageURL;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getProductBrand(): string
    {
        return $this->productBrand;
    }
}