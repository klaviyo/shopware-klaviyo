<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify\IdentifyProfileRequest;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class IdentifyProfileRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param IdentifyProfileRequest $object
     * @param string|null $format
     * @param array $context
     * @return array
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $customerProperties = $object->getCustomerProperties();

        return ['data' => ['type' => 'profile', 'attributes' => [
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

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof IdentifyProfileRequest;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            IdentifyProfileRequest::class => true,
        ];
    }
}
