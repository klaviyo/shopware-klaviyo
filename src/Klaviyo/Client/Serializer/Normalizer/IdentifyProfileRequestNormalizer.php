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

        $data = ['data' => ['type' => 'profile', 'attributes' => [
            'email' => $customerProperties->getEmail(),
            'phone_number' => $customerProperties->getPhoneNumber(),
            'first_name' => $customerProperties->getFirstName(),
            'last_name' => $customerProperties->getLastName(),
            'location' => [
                'address1' => $customerProperties->getAddress(),
                'address2' => $customerProperties->getAdditionalAddress(),
                'city' => $customerProperties->getCity(),
                'country' => $customerProperties->getCountry(),
                'region' => $customerProperties->getRegion(),
                'zip' => $customerProperties->getZip(),
            ],
            'organization' => $customerProperties->getCompany(),
            'title' => $customerProperties->getTitle(),
        ]]];

        $properties = array_filter([
            'birthday' => $customerProperties->getBirthday(),
            'language' => $customerProperties->getLocaleCode(),
            'salesChannelId' => $customerProperties->getSalesChannelId(),
            'salesChannelName' => $customerProperties->getSalesChannelName(),
            'customerGroup' => $customerProperties->getGroupName(),
            'accountType' => $customerProperties->getAccountType(),
            'boundedSalesChannelId' => $customerProperties->getBoundedSalesChannelId(),
            'boundedSalesChannelName' => $customerProperties->getBoundedSalesChannelName(),
            'vatId' => $customerProperties->getVatId(),
            'customerNumber' => $customerProperties->getCustomerNumber(),
            'customerId' => $customerProperties->getCustomerId(),
            'affiliateCode' => $customerProperties->getAffiliateCode(),
            'campaignCode' => $customerProperties->getCampaignCode(),
        ]);

        if (!empty($customerProperties->getCustomFields())) {
            $properties = array_merge($properties, $customerProperties->getCustomFields());
        }

        if (!empty($properties)) {
            $data['data']['attributes']['properties'] = $properties;
        }

        return $data;
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
