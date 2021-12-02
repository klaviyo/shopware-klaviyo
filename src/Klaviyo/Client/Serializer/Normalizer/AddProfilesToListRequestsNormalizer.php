<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileContactInfo;

class AddProfilesToListRequestsNormalizer extends AbstractNormalizer
{
    /**
     * @param AddProfilesToListRequest $object
     * @param string|null $format
     * @param array $context
     *
     * @return array
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $profiles = [];

        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $profiles[] = ['email' => $profile->getEmail()];
        }

        return [
            'profiles' => $profiles
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof AddProfilesToListRequest;
    }
}