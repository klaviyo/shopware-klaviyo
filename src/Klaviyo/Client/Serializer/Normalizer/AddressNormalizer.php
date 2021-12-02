<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\Address;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AddressNormalizer implements NormalizerInterface
{
    /**
     * @param Address $object
     * @param string|null $format
     * @param array $context
     *
     * @return array|\ArrayObject|bool|float|int|string|void|null
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        return [
            "FirstName" => $object->getFirstName(),
            "LastName" => $object->getLastName(),
            "Company" => $object->getCompany(),
            "Address1" => $object->getStreet(),
            "Address2" => $object->getStreet2(),
            "City" => $object->getCity(),
            "Region" => $object->getRegionName(),
            "RegionCode" => $object->getRegionCode(),
            "Country" => $object->getCountryName(),
            "CountryCode" => $object->getCountryCode(),
            "Zip" => $object->getZip(),
            "Phone" => $object->getPhone()
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Address;
    }
}