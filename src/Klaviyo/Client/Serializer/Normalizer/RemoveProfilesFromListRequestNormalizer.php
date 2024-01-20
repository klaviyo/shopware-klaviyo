<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;

class RemoveProfilesFromListRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param RemoveProfilesFromListRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array[]
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = [
            'type' => 'profile-subscription-bulk-delete-job',
            'attributes' => [
                'profiles' => [
                    'data' => []
                ]
            ],
            'relationships' => [
                'list' => [
                    'data' => [
                        'type' => 'list',
                        'id' => $object->getListId()
                    ]
                ]
            ]
        ];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $data['attributes']['profiles']['data'][] = [
                'type' => 'profile',
                'attributes' => [
                    'email' => $profile->getEmail()
                ]
            ];
        }

        return [
            'data' => $data
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof RemoveProfilesFromListRequest;
    }
}
