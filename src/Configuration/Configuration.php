<?php

namespace Klaviyo\Integration\Configuration;

use Klaviyo\Integration\Struct\PopUpConfiguration;

class Configuration implements ConfigurationInterface
{
    private bool $accountEnabled;
    private string $privateApiKey;
    private string $publicApiKey;
    private string $subscribersListName;
    private string $bisVariantField;
    private bool $trackDeletedAccountOrders;
    private bool $trackViewedProduct;
    private bool $trackRecentlyViewedItems;
    private bool $trackAddedToCart;
    private bool $trackStartedCheckout;
    private bool $trackPlacedOrder;
    private bool $trackOrderedProduct;
    private bool $trackFulfilledOrder;
    private bool $trackCanceledOrder;
    private bool $trackRefundedOrder;
    private bool $trackPaidOrder;
    private array $customFieldMapping;
    private bool $afterInteraction;
    private bool $trackSubscribedToBackInStock;
    private PopUpConfiguration $popUpConfiguration;

    public function __construct(
        bool $accountEnabled,
        string $privateApiKey,
        string $publicApiKey,
        string $subscribersListName,
        string $bisVariantField,
        bool $trackDeletedAccountOrders,
        bool $trackViewedProduct,
        bool $trackRecentlyViewedItems,
        bool $trackAddedToCart,
        bool $trackStartedCheckout,
        bool $trackPlacedOrder,
        bool $trackOrderedProduct,
        bool $trackFulfilledOrder,
        bool $trackCanceledOrder,
        bool $trackRefundedOrder,
        bool $trackPaidOrder,
        array $customFieldMapping,
        bool $afterInteraction,
        bool $trackSubscribedToBackInStock,
        PopUpConfiguration $popUpConfiguration
    ) {
        $this->accountEnabled = $accountEnabled;
        $this->privateApiKey = $privateApiKey;
        $this->publicApiKey = $publicApiKey;
        $this->subscribersListName = $subscribersListName;
        $this->bisVariantField = $bisVariantField;
        $this->trackDeletedAccountOrders = $trackDeletedAccountOrders;
        $this->trackViewedProduct = $trackViewedProduct;
        $this->trackRecentlyViewedItems = $trackRecentlyViewedItems;
        $this->trackAddedToCart = $trackAddedToCart;
        $this->trackStartedCheckout = $trackStartedCheckout;
        $this->trackPlacedOrder = $trackPlacedOrder;
        $this->trackOrderedProduct = $trackOrderedProduct;
        $this->trackFulfilledOrder = $trackFulfilledOrder;
        $this->trackCanceledOrder = $trackCanceledOrder;
        $this->trackRefundedOrder = $trackRefundedOrder;
        $this->trackPaidOrder = $trackPaidOrder;
        $this->customFieldMapping = $customFieldMapping;
        $this->afterInteraction = $afterInteraction;
        $this->trackSubscribedToBackInStock = $trackSubscribedToBackInStock;
        $this->popUpConfiguration = $popUpConfiguration;
    }

    public function isAccountEnabled(): bool
    {
        return $this->accountEnabled;
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

    public function isTrackDeletedAccountOrders(): bool
    {
        return $this->trackDeletedAccountOrders;
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

    public function isTrackPaidOrder(): bool
    {
        return $this->trackPaidOrder;
    }

    public function getCustomerCustomFieldMapping(): array
    {
        return $this->customFieldMapping;
    }

    public function isAfterInteraction(): bool
    {
        return $this->afterInteraction;
    }

    public function isTrackSubscribedToBackInStock(): bool
    {
        return $this->trackSubscribedToBackInStock;
    }

    public function getPopUpConfiguration(): PopUpConfiguration
    {
        return $this->popUpConfiguration;
    }

    public function getBisVariantField(): string
    {
        return $this->bisVariantField;
    }
}
