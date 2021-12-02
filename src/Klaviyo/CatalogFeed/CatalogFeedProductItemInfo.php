<?php

namespace Klaviyo\Integration\Klaviyo\CatalogFeed;

class CatalogFeedProductItemInfo implements \JsonSerializable
{
    private string $id;
    private string $productName;
    private string $productViewPageUrl;
    private string $productCoverImageUrl;
    private string $description;
    private float $price;
    private array $categories;

    public function __construct(
        string $id,
        string $title,
        string $productViewPageUrl,
        string $productCoverImageUrl,
        string $description,
        float $price,
        array $categories
    ) {
        $this->id = $id;
        $this->productName = $title;
        $this->productViewPageUrl = $productViewPageUrl;
        $this->productCoverImageUrl = $productCoverImageUrl;
        $this->description = $description;
        $this->price = $price;
        $this->categories = $categories;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductViewPageUrl(): string
    {
        return $this->productViewPageUrl;
    }

    public function getProductCoverImageUrl(): string
    {
        return $this->productCoverImageUrl;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function jsonSerialize()
    {
        return [
            '$id' => $this->getId(),
            '$title' => $this->getProductName(),
            '$link' => $this->getProductViewPageUrl(),
            '$image_link' => $this->getProductCoverImageUrl(),
            '$description' => $this->getDescription(),
            '$price' => $this->getPrice(),
            '$categories' => $this->getCategories(),
        ];
    }
}