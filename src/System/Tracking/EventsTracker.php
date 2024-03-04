<?php

declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;

class EventsTracker implements EventsTrackerInterface
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

    public function trackPlacedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if ($configuration->isTrackPlacedOrder()) {
                $placedOrderTrackingResult = $this->gateway->trackPlacedOrders($context, $channelId, $events);
                $trackingResult->mergeWith($placedOrderTrackingResult);
            }
        }

        return $trackingResult;
    }

    public function trackOrderedProducts(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if ($configuration->isTrackOrderedProduct()) {
                $orderedProductTrackingResult = $this->gateway->trackOrderedProducts($context, $channelId, $events);
                $trackingResult->mergeWith($orderedProductTrackingResult);
            }
        }

        return $trackingResult;
    }

    public function trackFulfilledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if (!$configuration->isTrackFulfilledOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackFulfilledOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackCanceledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if (!$configuration->isTrackCanceledOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackCancelledOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackRefundOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if (!$configuration->isTrackRefundedOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackRefundedOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackAddedToCart(Context $context, CartEventRequestBag $requestBag): void
    {
        foreach ($requestBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);

            if (!$configuration->isTrackAddedToCart()) {
                return;
            }

            $this->gateway->trackAddedToCartRequests($channelId, $events);
        }
    }

    public function trackCustomerWritten(Context $context, ProfileEventsBag $trackingBag): void
    {
        foreach ($trackingBag->all() as $channelId => $customerEntities) {
            // TODO: maybe add enable/disable setting in future?
            $customerCollection = new CustomerCollection();

            foreach ($customerEntities as $customer) {
                $customerCollection->add($customer);
            }

            $this->gateway->upsertCustomerProfiles($context, $channelId, $customerCollection);
        }
    }

    public function trackShippedOrder(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if (!$configuration->isTrackShippedOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackShippedOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackPaiedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            // Added paid config or not
            if (!$configuration->isTrackPaidOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackPaiedOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackPartiallyPaidOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult
    {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            // Added paid config or not
            if (!$configuration->isTrackPaidOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackPartiallyPaidOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackPartiallyShippedOrder(
        Context $context,
        OrderTrackingEventsBag $trackingBag
    ): OrderTrackingResult {
        $trackingResult = new OrderTrackingResult();

        foreach ($trackingBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            $context->orderIdentificationFlag = $configuration->getOrderIdentification();

            if (!$configuration->isTrackShippedOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackPartiallyShippedOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }
}
