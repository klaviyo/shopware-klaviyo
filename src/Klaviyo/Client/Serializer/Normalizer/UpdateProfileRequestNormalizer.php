<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileRequest;

class UpdateProfileRequestNormalizer extends AbstractNormalizer
{
    public function normalize($object, string $format = null, array $context = []): array
    {
        $customerProperties = $object->getCustomerProperties();

        return ['data' => ['type' => 'profile', 'id' => $object->getProfileId(), 'attributes' => [
            'email' => $customerProperties->getEmail(),
            'phone_number' => $customerProperties->getPhoneNumber(),
            'external_id' => $customerProperties->getId(),
            'first_name' => $customerProperties->getFirstName(),
            'last_name' => $customerProperties->getLastName(),
            'location' => [
                'address1' => $customerProperties->getAddress(),
                'city' => $customerProperties->getCity(),
                'country' => $customerProperties->getCountry(),
                'region' => $customerProperties->getRegion(),
                'zip' => $customerProperties->getZip(),
            ],
        ]]];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof UpdateProfileRequest;
    }
}
