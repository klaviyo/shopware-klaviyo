<?php

namespace Klaviyo\Integration\Configuration;

class Configuration implements ConfigurationInterface
{
    private string $privateApiKey;
    private string $publicApiKey;
    private string $subscribersListName;
    private int $catalogFeedProductsCount;
    private bool $trackViewedProduct;
    private bool $trackRecentlyViewedItems;
    private bool $trackAddedToCart;
    private bool $trackStartedCheckout;
    private bool $trackPlacedOrder;
    private bool $trackOrderedProduct;
    private bool $trackFulfilledOrder;
    private bool $trackCanceledOrder;
    private bool $trackRefundedOrder;
    private array $customFieldMapping;

    public function __construct(
        string $privateApiKey,
        string $publicApiKey,
        string $subscribersListName,
        int $catalogFeedProductsCount,
        bool $trackViewedProduct,
        bool $trackRecentlyViewedItems,
        bool $trackAddedToCart,
        bool $trackStartedCheckout,
        bool $trackPlacedOrder,
        bool $trackOrderedProduct,
        bool $trackFulfilledOrder,
        bool $trackCanceledOrder,
        bool $trackRefundedOrder,
        array $customFieldMapping
    ) {
        $this->privateApiKey = $privateApiKey;
        $this->publicApiKey = $publicApiKey;
        $this->subscribersListName = $subscribersListName;
        $this->catalogFeedProductsCount = $catalogFeedProductsCount;
        $this->trackViewedProduct = $trackViewedProduct;
        $this->trackRecentlyViewedItems = $trackRecentlyViewedItems;
        $this->trackAddedToCart = $trackAddedToCart;
        $this->trackStartedCheckout = $trackStartedCheckout;
        $this->trackPlacedOrder = $trackPlacedOrder;
        $this->trackOrderedProduct = $trackOrderedProduct;
        $this->trackFulfilledOrder = $trackFulfilledOrder;
        $this->trackCanceledOrder = $trackCanceledOrder;
        $this->trackRefundedOrder = $trackRefundedOrder;
        $this->customFieldMapping = $customFieldMapping;
    }

    public function getPrivateApiKey(): string
    {
        return $this->privateApiKey;
    }

    public function getPublicApiKey(): string
    {
        return $this->publicApiKey;
    }

    public function getSubscribersListName(): string
    {
        return $this->subscribersListName;
    }

    public function getCatalogFeedProductsCount(): int
    {
        return $this->catalogFeedProductsCount;
    }

    public function isTrackViewedProduct(): bool
    {
        return $this->trackViewedProduct;
    }

    public function isTrackRecentlyViewedItems(): bool
    {
        return $this->trackRecentlyViewedItems;
    }

    public function isTrackAddedToCart(): bool
    {
        return $this->trackAddedToCart;
    }

    public function isTrackStartedCheckout(): bool
    {
        return $this->trackStartedCheckout;
    }

    public function isTrackPlacedOrder(): bool
    {
        return $this->trackPlacedOrder;
    }

    public function isTrackOrderedProduct(): bool
    {
        return $this->trackOrderedProduct;
    }

    public function isTrackFulfilledOrder(): bool
    {
        return $this->trackFulfilledOrder;
    }

    public function isTrackCanceledOrder(): bool
    {
        return $this->trackCanceledOrder;
    }

    public function isTrackRefundedOrder(): bool
    {
        return $this->trackRefundedOrder;
    }

    public function getCustomerCustomFieldMapping(): array
    {
        return $this->customFieldMapping;
    }
}
