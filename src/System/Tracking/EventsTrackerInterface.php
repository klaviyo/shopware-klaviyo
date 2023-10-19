<?php declare(strict_types=1);

namespace Klaviyo\Integration\System\Tracking;

use Klaviyo\Integration\Klaviyo\Gateway\Result\OrderTrackingResult;
use Klaviyo\Integration\System\Tracking\Event\Cart\CartEventRequestBag;
use Klaviyo\Integration\System\Tracking\Event\Customer\ProfileEventsBag;
use Klaviyo\Integration\System\Tracking\Event\Order\OrderTrackingEventsBag;
use Shopware\Core\Framework\Context;

interface EventsTrackerInterface
{
    public const CUSTOMER_WRITTEN_EVENT = 'od-klaviyo-customer-written';

    public const SUBSCRIBER_EVENT_SUB = 'od-klaviyo-subscriber-subscribed';
    public const SUBSCRIBER_EVENT_UNSUB = 'od-klaviyo-subscriber-unsubscribed';
    public const SUBSCRIBER_EVENTS = [
        self::SUBSCRIBER_EVENT_SUB,
        self::SUBSCRIBER_EVENT_UNSUB,
    ];

    public const ORDER_EVENT_PLACED = 'od-klaviyo-order-placed';
    public const ORDER_EVENT_ORDERED_PRODUCT = 'od-klaviyo-ordered-product';
    public const ORDER_EVENT_CANCELED = 'od-klaviyo-order-canceled';
    public const ORDER_EVENT_REFUNDED = 'od-klaviyo-order-refunded';
    public const ORDER_EVENT_PAID = 'od-klaviyo-order-paid';
    public const ORDER_EVENT_SHIPPED = 'od-klaviyo-order-shipped';
    public const ORDER_EVENT_FULFILLED = 'od-klaviyo-order-fulfilled';
    public const ORDER_EVENTS = [
        self::ORDER_EVENT_PLACED => 'Order Placed',
        self::ORDER_EVENT_ORDERED_PRODUCT => 'Ordered Product',
        self::ORDER_EVENT_CANCELED => 'Order Cancelled',
        self::ORDER_EVENT_REFUNDED => 'Order Refunded',
        self::ORDER_EVENT_FULFILLED => 'Order Fulfilled',
        self::ORDER_EVENT_PAID => 'Order Paid',
        self::ORDER_EVENT_SHIPPED => 'Order Shipped',
    ];

    public function trackPlacedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackOrderedProducts(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackFulfilledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackCanceledOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackRefundOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackPaiedOrders(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackShippedOrder(Context $context, OrderTrackingEventsBag $trackingBag): OrderTrackingResult;

    public function trackAddedToCart(Context $context, CartEventRequestBag $requestBag);

    public function trackCustomerWritten(Context $context, ProfileEventsBag $trackingBag);
}
