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
        $profiles = $emails = [];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            if (in_array($profile->getEmail(), $emails)) {
                continue;
            }

            $emails[] = $profile->getEmail();

            $item = [
                'type' => 'profile',
                'email' => $profile->getEmail(),
            ];
            $localeCode = $profile->getLocaleCode();
            if ($localeCode !== null && $localeCode !== '') {
                $item['properties'] = ['language' => $localeCode];
            }
            $profiles[] = $item;
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
