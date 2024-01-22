<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\Address;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfoCollection;

class ShippedOrderEventTrackingRequest extends AbstractOrderEventTrackingRequest
{
    public function __construct(
        string $eventId,
        \DateTimeInterface $time,
        ?CustomerProperties $customerProperties,
        float $orderTotal,
        string $orderId,
        DiscountInfoCollection $discounts,
        OrderProductItemInfoCollection $products,
        ?Address $billingAddress,
        ?Address $shippingAddress
    ) {
        parent::__construct(
            $eventId,
            $time,
            $customerProperties,
            $orderTotal,
            $orderId,
            $discounts,
            $products,
            $billingAddress,
            $shippingAddress
        );
    }
}
