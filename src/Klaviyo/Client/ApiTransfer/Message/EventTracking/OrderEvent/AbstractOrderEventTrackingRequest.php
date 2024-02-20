<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\Address;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingRequest;

class AbstractOrderEventTrackingRequest extends EventTrackingRequest
{
    private float $orderTotal;
    private float $shippingTotal;
    private string $orderId;
    private DiscountInfoCollection $discounts;
    private OrderProductItemInfoCollection $products;
    private ?Address $billingAddress;
    private ?Address $shippingAddress;

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
        ?Address $shippingAddress
    ) {
        $this->orderTotal = $orderTotal;
        $this->shippingTotal = $shippingTotal;
        $this->orderId = $orderId;
        $this->discounts = $discounts;
        $this->products = $products;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;

        parent::__construct($eventId, $time, $customerProperties);
    }

    public function getOrderTotal(): float
    {
        return $this->orderTotal;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getDiscounts(): DiscountInfoCollection
    {
        return $this->discounts;
    }

    public function getProducts(): OrderProductItemInfoCollection
    {
        return $this->products;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }
    public function getShippingTotal(): float
    {
        return $this->shippingTotal;
    }
}
