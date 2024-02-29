<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;

class AddProfilesToListRequestsNormalizer extends AbstractNormalizer
{
    /**
     * @param AddProfilesToListRequest $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $profiles = [];
        $data = [
            'data' => [
                'type' => 'profile-bulk-import-job',
                'relationships' => ['lists' => ['data' => [['type' => 'list', 'id' => $object->getListId()]]]],
            ],
        ];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $profiles[] = [
                'type' => 'profile',
                'attributes' => [
                    'email' => $profile->getEmail(),
                    'first_name' => $profile->getFirstname(),
                    'last_name' => $profile->getLastname(),
                    'title' => $profile->getSalutation(),
                ],
            ];
        }

        $data['data']['attributes']['profiles']['data'] = $profiles;

        return $data;
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AddProfilesToListRequest;
    }
}
