<?php

namespace Klaviyo\Integration\Configuration;

use Klaviyo\Integration\Exception\InvalidConfigurationException;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurationFactory implements ConfigurationFactoryInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function create(?SalesChannelEntity $salesChannelEntity): ConfigurationInterface
    {
        $salesChannelId = $salesChannelEntity ? $salesChannelEntity->getId() : null;

        $privateApiKey = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.privateApiKey', $salesChannelId);
        if (!$privateApiKey) {
            throw new InvalidConfigurationException(
                'Klaviyo Integration Private Api Key configuration is not defined'
            );
        }

        $publicApiKey = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.publicApiKey', $salesChannelId);
        if (!$publicApiKey) {
            throw new InvalidConfigurationException(
                'Klaviyo Integration Public Api Key configuration is not defined'
            );
        }

        $listName = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync', $salesChannelId);
        if (!$listName) {
            throw new InvalidConfigurationException(
                'Klaviyo Integration List For Subscribers configuration is not defined'
            );
        }

        $subscribersSynchronizationTimeRaw = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.subscribersSynchronizationTime', $salesChannelId);
        if (!$subscribersSynchronizationTimeRaw) {
            throw new InvalidConfigurationException(
                'Klaviyo Subscribers synchronization time configuration is not defined'
            );
        }
        $subscribersSynchronizationTime = $this->convertTimeStringToDTO($subscribersSynchronizationTimeRaw);

        $catalogFeedProductsCount = $this->getIntConfiguration('catalogFeedProductsCount', $salesChannelId);

        $trackViewedProduct = $this->getBoolConfiguration('trackViewedProduct', $salesChannelId);
        $trackRecentlyViewedItems = $this->getBoolConfiguration(
            'trackRecentlyViewedItems',
            $salesChannelId
        );
        $trackAddedToCart = $this->getBoolConfiguration('trackAddedToCart', $salesChannelId);
        $trackStartedCheckout = $this->getBoolConfiguration('trackStartedCheckout', $salesChannelId);
        $trackPlacedOrder = $this->getBoolConfiguration('trackPlacedOrder', $salesChannelId);
        $trackOrderedProduct = $this->getBoolConfiguration('trackOrderedProduct', $salesChannelId);
        $trackFulfilledOrder = $this->getBoolConfiguration('trackFulfilledOrder', $salesChannelId);
        $trackCancelledOrder = $this->getBoolConfiguration('trackCancelledOrder', $salesChannelId);
        $trackRefundedOrder = $this->getBoolConfiguration('trackRefundedOrder', $salesChannelId);

        return new Configuration(
            $privateApiKey,
            $publicApiKey,
            $listName,
            $subscribersSynchronizationTime,
            $catalogFeedProductsCount,
            $trackViewedProduct,
            $trackRecentlyViewedItems,
            $trackAddedToCart,
            $trackStartedCheckout,
            $trackPlacedOrder,
            $trackOrderedProduct,
            $trackFulfilledOrder,
            $trackCancelledOrder,
            $trackRefundedOrder
        );
    }

    private function getBoolConfiguration(string $configurationName, ?string $salesChannelId): bool
    {
        $value = $this->systemConfigService
            ->get("KlaviyoIntegrationPlugin.config.{$configurationName}", $salesChannelId);
        if (!is_bool($value)) {
            throw new InvalidConfigurationException(
                "Klaviyo Integration '$configurationName' configuration is not defined"
            );
        }

        return $value;
    }

    private function getIntConfiguration(string $configurationName, ?string $salesChannelId): int
    {
        $value = $this->systemConfigService
            ->get("KlaviyoIntegrationPlugin.config.{$configurationName}", $salesChannelId);
        if (is_null($value)) {
            throw new InvalidConfigurationException(
                "Klaviyo Integration '$configurationName' configuration is not defined"
            );
        }
        if (!is_int($value)) {
            throw new InvalidConfigurationException(
                "Klaviyo Integration configuration[name: '$configurationName', value: '$value'] is not integer"
            );
        }

        return $value;
    }

    private function convertTimeStringToDTO(string $time): TimeInfo
    {
        $timeParts = explode(':', $time);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        // Check if time is valid
        $result = $now->setTime($timeParts[0], $timeParts[1]);
        if (!$result instanceof \DateTime) {
            throw new InvalidConfigurationException(sprintf('Invalid time value "%s"', $time));
        }

        return new TimeInfo($timeParts[0], $timeParts[1]);
    }
}