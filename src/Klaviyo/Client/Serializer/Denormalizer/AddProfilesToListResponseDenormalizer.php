<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class AddProfilesToListResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritDoc}
     *
     * @return AddProfilesToListResponse
     * @throws DeserializationException
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $addedProfiles = new ProfileInfoCollection();

        // If List id is invalid we receive an error in the message field but
        // if another error will happen details will be stored in the detail field
        if (!empty($data['detail']) || !empty($data['message'])) {
            $errorDetails = $data['detail'] ?? $data['message'];

            return new AddProfilesToListResponse(false, $addedProfiles, $errorDetails);
        }

        $addedProfiles = $this->denormalizeValue($data, ProfileInfoCollection::class);

        return new AddProfilesToListResponse(true, $addedProfiles);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === AddProfilesToListResponse::class;
    }
}