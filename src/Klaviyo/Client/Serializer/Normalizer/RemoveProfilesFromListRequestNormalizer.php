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
    public function normalize($object, string $format = null, array $context = [])
    {
        $emails = [];
        /** @var ProfileContactInfo $profile */
        foreach ($object->getProfiles() as $profile) {
            $emails[] = $profile->getEmail();
        }

        return [
            'emails' => $emails
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof RemoveProfilesFromListRequest;
    }
}