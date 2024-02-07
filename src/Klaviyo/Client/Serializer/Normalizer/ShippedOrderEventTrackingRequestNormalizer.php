<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\ShippedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ShippedOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, ShippedOrderEventTrackingRequest::class, 'Shipped Order');
    }

    /**
     * @param ShippedOrderEventTrackingRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     * @throws SerializationException
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $normalizedData = parent::normalize($object);

        return [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'time' => $normalizedData['time'],
                    'value' => $normalizedData['value'],
                    'unique_id' => $normalizedData['event_id'],
                    'properties' => $normalizedData['properties'],
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => [
                                'name' => $normalizedData['event']
                            ]
                        ]
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'id' => '',
                            'attributes' => $normalizedData['customer_properties']
                        ]
                    ]
                ]
            ]
        ];
    }
}
