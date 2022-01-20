<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Psr\Log\LoggerInterface;
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

            if ($configuration->isTrackPlacedOrder()) {
                $placedOrderTrackingResult = $this->gateway->trackPlacedOrders($context, $channelId, $events);
                $trackingResult->mergeWith($placedOrderTrackingResult);
            }

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
            if (!$configuration->isTrackRefundedOrder()) {
                continue;
            }

            $channelTrackingResult = $this->gateway->trackRefundedOrders($context, $channelId, $events);
            $trackingResult->mergeWith($channelTrackingResult);
        }

        return $trackingResult;
    }

    public function trackAddedToCart(Context $context, CartEventRequestBag $requestBag)
    {
        foreach ($requestBag->all() as $channelId => $events) {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);
            if (!$configuration->isTrackAddedToCart()) {
                return;
            }
//TODO: add result
            $this->gateway->trackAddedToCartRequests($channelId, $events);
        }
    }
}
