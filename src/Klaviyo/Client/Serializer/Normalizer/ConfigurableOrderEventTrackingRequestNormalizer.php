<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\AbstractOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\DiscountInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\DTO\OrderProductItemInfo;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

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
     *
     * @throws SerializationException
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());

        if (
            !empty($context) && !empty($context['eventType'])
            && in_array(
                $context['eventType'],
                [
                    'FulfilledOrder',
                    'CancelledOrder',
                    'ShippedOrder',
                    'PaidOrder',
                    'RefundedOrder',
                    'PartiallyShippedOrder',
                    'PartiallyPaidOrder',
                ]
            )
        ) {
            unset($customerProperties['phone_number']);

            if ('PartiallyShippedOrder' === $context['eventType']) {
                $this->eventName = 'Partially Shipped Order';
            }

            if ('PartiallyPaidOrder' === $context['eventType']) {
                $this->eventName = 'Partially Paid Order';
            }
        }

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
                'Brand' => $product->getBrand(),
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
            'OrderId' => $object->getOrderId(),
            'Categories' => array_unique($categories),
            'ItemNames' => $itemNames,
            'Brands' => array_unique($brands),
            'DiscountCode' => implode(',', $discountCodes),
            'DiscountValue' => $discountTotal,
            'ShippingCosts' => $object->getShippingTotal(),
            'Items' => $normalizedItems,
            'BillingAddress' => $billingAddress,
            'ShippingAddress' => $shippingAddress,
        ];

        if (property_exists($object, 'reason')) {
            $properties['Reason'] = $object->getReason();
        }

        return [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $object->getTime()->format('Y-m-d\TH:i:s'),
                    'value' => $object->getOrderTotal(),
                    'unique_id' => $object->getEventId(),
                    'properties' => $properties,
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => $this->eventName,
                            ],
                        ],
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'attributes' => $customerProperties,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof $this->className;
    }
}
