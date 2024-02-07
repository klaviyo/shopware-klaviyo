<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\CustomerProperties;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CustomerPropertiesNormalizer implements NormalizerInterface
{
    /**
     * @param CustomerProperties $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $properties = array_merge(
            [
                'Birthday' => $object->getBirthday(),
                'salesChannelId' => $object->getSalesChannelId(),
                'salesChannelName' => $object->getSalesChannelName(),
                'boundedSalesChannelId' => $object->getBoundedSalesChannelId(),
                'boundedSalesChannelName' => $object->getBoundedSalesChannelName(),
                'language' => $object->getLocaleCode()
            ],
            $object->getCustomFields()
        );

        return [
                'external_id' => $object->getId(),
                'email' => $object->getEmail(),
                'first_name' => $object->getFirstName(),
                'last_name' => $object->getLastName(),
                'phone_number' => $object->getPhoneNumber(),
                'location' => [
                    'address1' => $object->getAddress(),
                    'city' => $object->getCity(),
                    'region' => $object->getRegion(),
                    'country' => $object->getCountry(),
                    'zip' => $object->getZip(),
                ],
                'properties' => $properties,
            ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof CustomerProperties;
    }
}
