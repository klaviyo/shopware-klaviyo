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
        $profiles = $emails = [];
        $data = [
            'data' => [
                'type' => 'profile-subscription-bulk-create-job',
                'relationships' => ['list' => ['data' => ['type' => 'list', 'id' => $object->getListId()]]],
            ],
        ];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            if (in_array($profile->getEmail(), $emails)) {
                continue;
            }

            $emails[] = $profile->getEmail();

            $profiles[] = [
                'type' => 'profile',
                'attributes' => [
                    'email' => $profile->getEmail(),
                    'subscriptions' => [
                        'email' => [
                            'marketing' => [
                                'consent' => 'SUBSCRIBED',
                                //TODO Possibly will be added later
                                //'consented_at' => $profile->getCreatedAt()?->format('Y-m-d\TH:i:s'),
                            ]
                        ]
                    ]
                ],
            ];
        }

        $data['data']['attributes']['profiles']['data'] = $profiles;
        $data['data']['attributes']['historical_import'] = false;

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof AddProfilesToListRequest;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AddProfilesToListRequest::class => true,
        ];
    }
}
