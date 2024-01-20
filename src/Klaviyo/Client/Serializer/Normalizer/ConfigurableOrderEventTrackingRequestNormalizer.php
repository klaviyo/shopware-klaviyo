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
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws SerializationException
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
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

        $properties = [
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

        if (property_exists($object, 'reason')) {
            $properties['Reason'] = $object->getReason();
        }

        return [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $object->getTime()->format('Y-m-d\TH:i:s'),
                    'value' => $object->getOrderTotal(),
                    'unique_id' => $object->getEventId() . '_' . $object->getTime()->getTimestamp(),
                    'properties' => $properties,
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => $this->eventName
                            ]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'attributes' => $customerProperties
                        ]
                    ]
                ]
            ]
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof $this->className;
    }
}
