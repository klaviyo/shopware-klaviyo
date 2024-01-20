<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListRequest;

class SubscribeToListRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param $object
     * @param string|null $format
     * @param array $context
     * @return array[]
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $profiles = [];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $profiles[] = [
                'type' => 'profile',
                'email' => $profile->getEmail(),
            ];
        }

        return [
            'data' => $profiles,
        ];
    }

    /**
     * @param $data
     * @param string|null $format
     * @return bool
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribeToListRequest;
    }
}
