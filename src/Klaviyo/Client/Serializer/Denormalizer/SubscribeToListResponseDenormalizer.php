<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class SubscribeToListResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @param $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return SubscribeToListResponse
     * @throws DeserializationException
     * @throws ExceptionInterface
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): SubscribeToListResponse {
        $addedProfiles = new ProfileInfoCollection();

        // If List id is invalid we receive an error in the message field but
        // if another error will happen details will be stored in the detail field
        if (!empty($data['errors'][0]['detail'])) {
            $errorDetails = $data['errors'][0]['detail'];

            return new SubscribeToListResponse(false, $addedProfiles, $errorDetails);
        }

        $addedProfiles = $this->denormalizeValue($data, ProfileInfoCollection::class);

        return new SubscribeToListResponse(true, $addedProfiles);
    }

    /**
     * @param $data
     * @param string $type
     * @param string|null $format
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return SubscribeToListResponse::class === $type;
    }
}
