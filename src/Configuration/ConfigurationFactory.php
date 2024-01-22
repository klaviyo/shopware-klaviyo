<?php

namespace Klaviyo\Integration\Configuration;

use Klaviyo\Integration\Exception\InvalidConfigurationException;
use Klaviyo\Integration\Struct\PopUpConfiguration;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurationFactory implements ConfigurationFactoryInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function create(string $salesChannelId = null): ConfigurationInterface
    {
        $accountEnabled = $this->getBoolConfiguration('enabled', $salesChannelId);
        $privateApiKey = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.privateApiKey', $salesChannelId);
        if (!$privateApiKey) {
            throw new InvalidConfigurationException('Klaviyo Integration Private Api Key configuration is not defined');
        }

        $publicApiKey = $this->systemConfigService
            ->get('KlaviyoIntegrationPlugin.config.publicApiKey', $salesChannelId);
        if (!$publicApiKey) {
            throw new InvalidConfigurationException('Klaviyo Integration Public Api Key configuration is not defined');
        }

        $listId = $this->systemConfigService
            ->get('klavi_overd.config.klaviyoListForSubscribersSync', $salesChannelId);
        if (!$listId) {
            throw new InvalidConfigurationException(
                'Klaviyo Integration List For Subscribers configuration is not defined'
            );
        }

        $bisVariantField =
            $this->systemConfigService->get('klavi_overd.config.bisVariantField', $salesChannelId) ?? 'product-number';
        $orderIdentification =
            $this->systemConfigService->get('klavi_overd.config.orderIdentification', $salesChannelId) ?? 'order-id';
        $trackDeletedAccountOrders = $this->getBoolConfiguration('trackDeletedAccountOrders', $salesChannelId);
        $trackViewedProduct = $this->getBoolConfiguration('trackViewedProduct', $salesChannelId);
        $trackRecentlyViewedItems = $this->getBoolConfiguration('trackRecentlyViewedItems', $salesChannelId);
        $trackAddedToCart = $this->getBoolConfiguration('trackAddedToCart', $salesChannelId);
        $trackStartedCheckout = $this->getBoolConfiguration('trackStartedCheckout', $salesChannelId);
        $trackPlacedOrder = $this->getBoolConfiguration('trackPlacedOrder', $salesChannelId);
        $trackOrderedProduct = $this->getBoolConfiguration('trackOrderedProduct', $salesChannelId);
        $trackFulfilledOrder = $this->getBoolConfiguration('trackFulfilledOrder', $salesChannelId);
        $trackCancelledOrder = $this->getBoolConfiguration('trackCancelledOrder', $salesChannelId);
        $trackRefundedOrder = $this->getBoolConfiguration('trackRefundedOrder', $salesChannelId);
        $trackPaidOrder = $this->getBoolConfiguration('trackPaidOrder', $salesChannelId);
        $trackShippedOrder = $this->getBoolConfiguration('trackShippedOrder', $salesChannelId);
        $dailySubscribersSynchronization = $this->getBoolConfiguration('dailySynchronization', $salesChannelId);
        $dailySubscribersSyncTime =
            $this->systemConfigService->get('klavi_overd.config.dailySynchronizationTime', $salesChannelId) ?? '';

        $trackSubscribedToBackInStock = $this->getBoolConfiguration('trackSubscribedToBackInStock', $salesChannelId);
        $afterInteraction = $this->systemConfigService->getBool(
            'klavi_overd.config.isInitializeKlaviyoAfterInteraction',
            $salesChannelId
        );

        $mapping = $this->systemConfigService
                ->get('KlaviyoIntegrationPlugin.config.customerFieldMapping', $salesChannelId) ?? [];

        $popUpConfiguration = new PopUpConfiguration(
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.popUpOpenBtnColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.popUpOpenBtnBgColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.popUpCloseBtnColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.popUpCloseBtnBgColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.subscribeBtnColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.subscribeBtnBgColor', $salesChannelId),
            $this->systemConfigService->getString('KlaviyoIntegrationPlugin.config.popUpAdditionalClasses', $salesChannelId)
        );

        $cookieConsent =
            $this->systemConfigService->get('klavi_overd.config.cookieConsent', $salesChannelId) ?? 'shopware';

        if (is_array($mapping)) {
            foreach ($mapping as $mappingId => $mappingAssociation) {
                unset($mapping[$mappingId]);

                if (!empty($mappingAssociation['customLabel']) && !empty($mappingAssociation['customFieldName'])) {
                    $mapping[$mappingAssociation['customFieldName']] = $mappingAssociation['customLabel'];
                }
            }
        }

        return new Configuration(
            $accountEnabled,
            trim($privateApiKey),
            trim($publicApiKey),
            trim($listId),
            $bisVariantField,
            $orderIdentification,
            $trackDeletedAccountOrders,
            $trackViewedProduct,
            $trackRecentlyViewedItems,
            $trackAddedToCart,
            $trackStartedCheckout,
            $trackPlacedOrder,
            $trackOrderedProduct,
            $trackFulfilledOrder,
            $trackCancelledOrder,
            $trackRefundedOrder,
            $trackPaidOrder,
            $trackShippedOrder,
            $mapping,
            $afterInteraction,
            $trackSubscribedToBackInStock,
            $popUpConfiguration,
            $cookieConsent,
            $dailySubscribersSynchronization,
            $dailySubscribersSyncTime
        );
    }

    private function getBoolConfiguration(string $configurationName, ?string $salesChannelId): bool
    {
        return $this->systemConfigService
            ->getBool("KlaviyoIntegrationPlugin.config.{$configurationName}", $salesChannelId);
    }
}
