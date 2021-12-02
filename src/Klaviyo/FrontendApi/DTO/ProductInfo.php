<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

class ProductInfo implements \JsonSerializable
{
    private string $name;
    private string $id;
    private string $sku;
    private array $categoryNames;
    private string $imageUrl;
    private string $viewPageUrl;
    private ?string $brand;
    private ?float $price;
    private ?float $compareAtPrice;

    public function __construct(
        string $name,
        string $id,
        string $sku,
        array $categoryNames,
        string $imageUrl,
        string $viewPageUrl,
        ?string $brand,
        ?float $price,
        ?float $compareAtPrice
    ) {
        $this->name = $name;
        $this->id = $id;
        $this->sku = $sku;
        $this->categoryNames = $categoryNames;
        $this->imageUrl = $imageUrl;
        $this->viewPageUrl = $viewPageUrl;
        $this->brand = $brand;
        $this->price = $price;
        $this->compareAtPrice = $compareAtPrice;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getCategoryNames(): array
    {
        return $this->categoryNames;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function getViewPageUrl(): string
    {
        return $this->viewPageUrl;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getCompareAtPrice(): ?float
    {
        return $this->compareAtPrice;
    }

    public function jsonSerialize()
    {
        return [
            'ProductName' => $this->getName(),
            'ProductID' => $this->getId(),
            'SKU' => $this->getSku(),
            'Categories' => $this->getCategoryNames(),
            'ImageURL' => $this->getImageUrl(),
            'URL' => $this->getViewPageUrl(),
            'Brand' => $this->getBrand(),
            'Price' => $this->getPrice(),
            'CompareAtPrice' => $this->getCompareAtPrice(),
        ];
    }
}