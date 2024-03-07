<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileRequest;

class UpdateProfileRequestNormalizer extends AbstractNormalizer
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $customerProperties = $object->getCustomerProperties();

        $properties = array_merge(
            [
                'Birthday' => $customerProperties->getBirthday(),
                'salesChannelId' => $customerProperties->getSalesChannelId(),
                'salesChannelName' => $customerProperties->getSalesChannelName(),
                'boundedSalesChannelId' => $customerProperties->getBoundedSalesChannelId(),
                'boundedSalesChannelName' => $customerProperties->getBoundedSalesChannelName(),
                'language' => $customerProperties->getLocaleCode(),
            ],
            $customerProperties->getCustomFields()
        );

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
            'properties' => $properties,
        ]]];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof UpdateProfileRequest;
    }
}
