<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class AddProfilesToListResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @throws DeserializationException|ExceptionInterface
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): AddProfilesToListResponse {
        $addedProfiles = new ProfileInfoCollection();

        // If List id is invalid we receive an error in the message field but
        // if another error will happen details will be stored in the detail field
        if (!empty($data['errors'][0]['detail'])) {
            $errorDetails = $data['errors'][0]['detail'];

            return new AddProfilesToListResponse(false, $addedProfiles, $errorDetails);
        }

        $addedProfiles = $this->denormalizeValue($data, ProfileInfoCollection::class);

        return new AddProfilesToListResponse(true, $addedProfiles);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return AddProfilesToListResponse::class === $type;
    }
}
