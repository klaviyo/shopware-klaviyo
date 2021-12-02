<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\DTO;

class CheckoutLineItemInfo implements \JsonSerializable
{
    private string $name;
    private string $id;
    private string $sku;
    private array $categoryNames;
    private string $imageUrl;
    private string $viewPageUrl;
    private float $quantity;
    private ?float $itemPrice;
    private ?float $rowTotal;

    public function __construct(
        string $name,
        string $id,
        string $sku,
        array $categoryNames,
        string $imageUrl,
        string $viewPageUrl,
        float $quantity,
        ?float $itemPrice,
        ?float $rowTotal
    ) {
        $this->name = $name;
        $this->id = $id;
        $this->sku = $sku;
        $this->categoryNames = $categoryNames;
        $this->imageUrl = $imageUrl;
        $this->viewPageUrl = $viewPageUrl;
        $this->quantity = $quantity;
        $this->itemPrice = $itemPrice;
        $this->rowTotal = $rowTotal;
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

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getItemPrice(): ?float
    {
        return $this->itemPrice;
    }

    public function getRowTotal(): ?float
    {
        return $this->rowTotal;
    }


    public function jsonSerialize()
    {
        return [
            'ProductID' => $this->getId(),
            'SKU' => $this->getSku(),
            'ProductName' => $this->getName(),
            'Quantity' => $this->getQuantity(),
            'ItemPrice' => $this->getItemPrice(),
            'RowTotal' => $this->getRowTotal(),
            'ProductURL' => $this->getViewPageUrl(),
            'ImageURL' => $this->getImageUrl(),
            'ProductCategories' => $this->getCategoryNames(),
        ];
    }
}