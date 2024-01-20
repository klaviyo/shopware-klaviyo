<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\OrderEvent\RefundedOrderEventTrackingRequest;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class RefundedOrderEventTrackingRequestNormalizer extends ConfigurableOrderEventTrackingRequestNormalizer
{
    public function __construct(ConfigurationInterface $configuration)
    {
        parent::__construct($configuration, RefundedOrderEventTrackingRequest::class, 'Refunded Order');
    }

    /**
     * @param RefundedOrderEventTrackingRequest $object
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

        $normalizedData['properties']['Reason'] = $object->getReason();

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
