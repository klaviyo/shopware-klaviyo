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
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return array_merge(
            [
                '$id' => $object->getId(),
                '$email' => $object->getEmail(),
                '$first_name' => $object->getFirstName(),
                '$last_name' => $object->getLastName(),
                '$address1' => $object->getAddress(),
                '$phone_number' => $object->getPhoneNumber(),
                '$city' => $object->getCity(),
                '$region' => $object->getRegion(),
                '$country' => $object->getCountry(),
                '$zip' => $object->getZip(),
                'Birthday' => $object->getBirthday()
            ],
            $object->getCustomFields()
        );
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof CustomerProperties;
    }
}
