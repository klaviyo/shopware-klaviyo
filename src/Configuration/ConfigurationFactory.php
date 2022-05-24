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

    public function create(?string $salesChannelId = null): ConfigurationInterface
    {
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

        $trackSubscribedToBackInStock = $this->getBoolConfiguration('trackSubscribedToBackInStock', $salesChannelId);
        $afterInteraction = $this->systemConfigService->getBool('KlaviyoIntegrationPlugin.config.isInitializeKlaviyoAfterInteraction', $salesChannelId);

        $mapping = $this->systemConfigService
                ->get('KlaviyoIntegrationPlugin.config.customerFieldMapping', $salesChannelId) ?? [];

        $popUpConfiguration = new PopUpConfiguration(
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.popUpOpenBtnColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.popUpOpenBtnBgColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.popUpCloseBtnColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.popUpCloseBtnBgColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.subscribeBtnColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.subscribeBtnBgColor', $salesChannelId),
            $this->systemConfigService->get('KlaviyoIntegrationPlugin.config.popUpAdditionalClasses', $salesChannelId)
        );


        if (is_array($mapping)) {
            foreach ($mapping as $mappingId => $mappingAssociation) {
                unset($mapping[$mappingId]);

                if (!empty($mappingAssociation['customLabel']) && !empty($mappingAssociation['customFieldName'])) {
                    $mapping[$mappingAssociation['customFieldName']] = $mappingAssociation['customLabel'];
                }
            }
        }

        return new Configuration(
            $privateApiKey,
            $publicApiKey,
            $listName,
            $trackViewedProduct,
            $trackRecentlyViewedItems,
            $trackAddedToCart,
            $trackStartedCheckout,
            $trackPlacedOrder,
            $trackOrderedProduct,
            $trackFulfilledOrder,
            $trackCancelledOrder,
            $trackRefundedOrder,
            $mapping,
            $afterInteraction,
            $trackSubscribedToBackInStock,
            $popUpConfiguration
        );
    }

    private function getBoolConfiguration(string $configurationName, ?string $salesChannelId): bool
    {
        return $this->systemConfigService
            ->getBool("KlaviyoIntegrationPlugin.config.{$configurationName}", $salesChannelId);
    }
}
