<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class RemoveProfilesFromListResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (empty($data)) {
            return new RemoveProfilesFromListResponse(true);
        }

        if (!is_array($data)) {
            throw new DeserializationException('Unexpected value, result expected to be empty string or an array');
        }

        if (!empty($data['detail'])) {
            // If List id is invalid we receive an error in the message key
            $errorDetails = $data['detail'];
        } elseif (!empty($data['message'])) {
            // If Api key is invalid we receive an error in the message key
            $errorDetails = $data['message'];
        } else {
            $errorDetails = 'Error details was not provided';
        }

        return new RemoveProfilesFromListResponse(false, $errorDetails);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === RemoveProfilesFromListResponse::class;
    }
}