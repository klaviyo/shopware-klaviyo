<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\Address;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfoCollection;

class CanceledOrderEventTrackingRequest extends AbstractOrderEventTrackingRequest
{
    private string $reason;

    public function __construct(
        string $eventId,
        \DateTimeInterface $time,
        ?CustomerProperties $customerProperties,
        float $orderTotal,
        float $shippingTotal,
        string $orderId,
        DiscountInfoCollection $discounts,
        OrderProductItemInfoCollection $products,
        ?Address $billingAddress,
        ?Address $shippingAddress,
        string $reason
    ) {
        parent::__construct(
            $eventId,
            $time,
            $customerProperties,
            $orderTotal,
            $shippingTotal,
            $orderId,
            $discounts,
            $products,
            $billingAddress,
            $shippingAddress
        );

        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
