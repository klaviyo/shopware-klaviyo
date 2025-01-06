<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\CartEvent\DTO;

class CartProductInfo
{
    private string $id;
    private string $sku;
    private string $name;
    private int $quantity;
    private float $price;
    private float $rowTotal;
    private string $imageUrl;
    private string $viewPageUrl;
    private array $productCategories;
    private string $brand;
    private array  $customOptions;

    public function __construct(
        string $id,
        string $sku,
        string $name,
        int $quantity,
        float $price,
        float $rowTotal,
        string $imageUrl,
        string $viewPageUrl,
        array $productCategories,
        string $brand,
        array $customOptions
    ) {
        $this->id = $id;
        $this->sku = $sku;
        $this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->rowTotal = $rowTotal;
        $this->imageUrl = $imageUrl;
        $this->viewPageUrl = $viewPageUrl;
        $this->productCategories = $productCategories;
        $this->brand = $brand;
        $this->customOptions = $customOptions;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getRowTotal(): float
    {
        return $this->rowTotal;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getViewPageUrl(): string
    {
        return $this->viewPageUrl;
    }

    public function getProductCategories(): array
    {
        return $this->productCategories;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getCustomOptions(): array
    {
        return $this->customOptions;
    }
}
