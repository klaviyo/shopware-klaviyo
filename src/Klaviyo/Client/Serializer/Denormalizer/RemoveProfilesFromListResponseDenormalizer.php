<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class RemoveProfilesFromListResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @throws DeserializationException
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): RemoveProfilesFromListResponse {
        if (empty($data)) {
            return new RemoveProfilesFromListResponse(true);
        }

        if (!is_array($data)) {
            throw new DeserializationException('Unexpected value, result expected to be empty string or an array');
        }

        if (!empty($data['errors'][0]['detail'])) {
            // If List id is invalid we receive an error in the message key
            $errorDetails = $data['errors'][0]['detail'];
        } else {
            $errorDetails = 'Error details was not provided';
        }

        return new RemoveProfilesFromListResponse(false, $errorDetails);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return RemoveProfilesFromListResponse::class === $type;
    }
}
