<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\AbstractOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfo;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;

class ConfigurableOrderEventTrackingRequestNormalizer extends AbstractNormalizer
{
    private string $eventName;
    private string $className;

    public function __construct(ConfigurationInterface $configuration, string $className, string $eventName)
    {
        parent::__construct($configuration);

        $this->className = $className;
        $this->eventName = $eventName;
    }

    /**
     * @param AbstractOrderEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());

        $categories = [];
        $itemNames = [];
        $brands = [];
        $normalizedItems = [];
        /** @var OrderProductItemInfo $product */
        foreach ($object->getProducts() as $product) {
            $categories = array_merge($categories, $product->getCategories());
            $itemNames[] = $product->getProductName();
            $brands[] = $product->getBrand();
            $normalizedItems[] = [
                'ProductID' => $product->getProductId(),
                'SKU' => $product->getSku(),
                'ProductName' => $product->getProductName(),
                'Quantity' => $product->getQuantity(),
                'ItemPrice' => $product->getItemPrice(),
                'RowTotal' => $product->getRowTotal(),
                'ProductURL' => $product->getProductUrl(),
                'ImageURL' => $product->getImageUrl(),
                'Categories' => $product->getCategories(),
                'Brand' => $product->getBrand()
            ];
        }

        $discountCodes = [];
        $discountTotal = 0;
        /** @var DiscountInfo $discount */
        foreach ($object->getDiscounts() as $discount) {
            $discountCodes[] = $discount->getCode();
            $discountTotal += $discount->getValue();
        }

        $billingAddress = $this->normalizeObject($object->getBillingAddress());
        $shippingAddress = $this->normalizeObject($object->getShippingAddress());

        switch ($this->eventName) {
            case 'Fulfilled Order': $orderTotalKey = 'FulfilledOrderValue';
                break;
            case 'Cancelled Order': $orderTotalKey = 'CancelledOrderValue';
                break;
            case 'Refunded Order': $orderTotalKey = 'RefundedOrderValue';
                break;
            case 'Paid Order': $orderTotalKey = 'PaidOrderValue';
                break;
            default: $orderTotalKey = 'PlacedOrderValue';
        }

        $properties = [
            $orderTotalKey => $object->getOrderTotal(),
            '$event_id' => $object->getEventId(),
            'OrderId' => $object->getOrderId(),
            'Categories' => array_unique($categories),
            'ItemNames' => $itemNames,
            'Brands' => array_unique($brands),
            'DiscountCode' => implode(',', $discountCodes),
            'DiscountValue' => $discountTotal,
            'Items' => $normalizedItems,
            'BillingAddress' => $billingAddress,
            'ShippingAddress' => $shippingAddress,
        ];

        return [
            'token' => $this->getToken(),
            'event' => $this->eventName,
            'customer_properties' => $customerProperties,
            'properties' => $properties,
            'time' => $object->getTime()->getTimestamp()
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof $this->className;
    }
}
