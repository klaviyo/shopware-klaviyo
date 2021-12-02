<?php

namespace Klaviyo\Integration\Tracking;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class EventsTracker
{
    private KlaviyoGateway $gateway;
    private ConfigurationRegistry $configurationRegistry;
    private LoggerInterface $logger;

    public function __construct(
        KlaviyoGateway $gateway,
        ConfigurationRegistry $configurationRegistry,
        LoggerInterface $logger
    ) {
        $this->gateway = $gateway;
        $this->configurationRegistry = $configurationRegistry;
        $this->logger = $logger;
    }

    public function trackPlacedOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity
    ): bool {
        $configuration = $this->configurationRegistry->getConfiguration($salesChannelEntity);

        $success = true;
        if ($configuration->isTrackPlacedOrder()) {
            if (!$this->gateway->trackPlacedOrder($context, $salesChannelEntity, $orderEntity)) {
                $success = false;
                $this->logger->error('Unable to track klaviyo placed order event');
            }
        }
        if ($configuration->isTrackOrderedProduct()) {
            if (!$this->gateway->trackOrderedProducts($context, $salesChannelEntity, $orderEntity)) {
                $success = false;
                $this->logger->error('Unable to track klaviyo ordered products event');
            }
        }

        return $success;
    }

    public function trackFulfilledOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ) {
        $configuration = $this->configurationRegistry->getConfiguration($salesChannelEntity);
        if (!$configuration->isTrackFulfilledOrder()) {
            return;
        }

        if (!$this->gateway->trackFulfilledOrder($context, $salesChannelEntity, $orderEntity, $eventHappenedDateTime)) {
            $this->logger->error('Unable to track klaviyo fulfilled order event');
        }
    }

    public function trackCanceledOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ) {
        $configuration = $this->configurationRegistry->getConfiguration($salesChannelEntity);
        if (!$configuration->isTrackCanceledOrder()) {
            return;
        }

        if (!$this->gateway->trackCancelledOrder($context, $salesChannelEntity, $orderEntity, $eventHappenedDateTime)) {
            $this->logger->error('Unable to track klaviyo canceled order event');
        }
    }

    public function trackRefundOrder(
        Context $context,
        SalesChannelEntity $salesChannelEntity,
        OrderEntity $orderEntity,
        \DateTimeInterface $eventHappenedDateTime
    ) {
        $configuration = $this->configurationRegistry->getConfiguration($salesChannelEntity);
        if (!$configuration->isTrackRefundedOrder()) {
            return;
        }

        if (!$this->gateway->trackRefundedOrder($context, $salesChannelEntity, $orderEntity, $eventHappenedDateTime)) {
            $this->logger->error('Unable to track klaviyo refunded order event');
        }
    }

    public function trackAddedToCart(SalesChannelContext $context, Cart $cart, LineItem $lineItem)
    {
        $configuration = $this->configurationRegistry->getConfiguration($context->getSalesChannel());
        if (!$configuration->isTrackAddedToCart()) {
            return;
        }

        if (!$this->gateway->trackAddedToCart($context, $cart, $lineItem)) {
            $this->logger->error('Unable to track klaviyo added to cart event');
        }
    }
}