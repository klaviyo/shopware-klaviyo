<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO;

class OrderProductItemInfo
{
    private string $productId;
    private string $sku;
    private string $productName;
    private int $quantity;
    private float $itemPrice;
    private float $rowTotal;
    private string $productUrl;
    private string $imageUrl;
    private array $categories;
    private string $brand;

    public function __construct(
        string $productId,
        string $sku,
        string $productName,
        int $quantity,
        float $itemPrice,
        float $rowTotal,
        string $productUrl,
        string $imageUrl,
        array $categories,
        string $brand
    ) {
        $this->productId = $productId;
        $this->sku = $sku;
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->itemPrice = $itemPrice;
        $this->rowTotal = $rowTotal;
        $this->productUrl = $productUrl;
        $this->imageUrl = $imageUrl;
        $this->categories = $categories;
        $this->brand = $brand;
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

    public function getItemPrice(): float
    {
        return $this->itemPrice;
    }

    public function getRowTotal(): float
    {
        return $this->rowTotal;
    }

    public function getProductUrl(): string
    {
        return $this->productUrl;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }
}