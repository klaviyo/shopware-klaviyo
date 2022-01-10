<?php

namespace Klaviyo\Integration\Tracking;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\OrderTrackingBag;
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

    /**
     * @param Context $context
     * @param OrderTrackingBag $trackingBag
     * @return OrderTrackingResult
     */
    public function trackPlacedOrders(Context $context, OrderTrackingBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->getIterator() as $channelId => $orders) {
            $configuration = $this->configurationRegistry->getConfigurationByChannelId($channelId);

            if ($configuration->isTrackPlacedOrder()) {
                $placedOrderTrackingResult = $this->gateway->trackPlacedOrders($context, $channelId, $orders);
                $trackingResult->mergeWith($placedOrderTrackingResult);
            }

            if ($configuration->isTrackOrderedProduct()) {
                $orderedProductTrackingResult = $this->gateway->trackOrderedProducts($context, $channelId, $orders);
                $trackingResult->mergeWith($orderedProductTrackingResult);
            }
        }

        return $trackingResult;
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
