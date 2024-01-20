<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\RealSubscribersToKlaviyoRequest;

class RealSubscribersToKlaviyoRequestNormalizer extends AbstractNormalizer
{
    /**
     * @return array[]
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = [
            'data' => [
                'type' => 'profile-subscription-bulk-create-job',
                'relationships' => ['list' => ['data' => ['type' => 'list', 'id' => $object->getListId()]]],
            ],
        ];

        $profiles = [];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $profiles['data'][] = [
                'type' => 'profile',
                'attributes' => [
                    'email' => $profile->getEmail(),
                ],
            ];
        }

        $data['data']['attributes'] = ['custom_source' => 'Marketing Event', 'profiles' => $profiles];

        return $data;
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof RealSubscribersToKlaviyoRequest;
    }
}
