<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class GetListProfilesResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @param $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return GetListProfilesResponse
     *
     * @throws DeserializationException
     * @throws ExceptionInterface
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): GetListProfilesResponse {
        $profilesInfoCollection = new ProfileInfoCollection();

        if (!empty($data['errors'][0]['detail'])) {
            $errorDetails = $data['errors'][0]['detail'];

            return new GetListProfilesResponse(false, $profilesInfoCollection, null, $errorDetails);
        }

        if (!isset($data['records']) || !is_array($data['records'])) {
            throw new DeserializationException(
                'Decoded GetProfilesListResponse expected to have records field with array of profiles'
            );
        }

        $profilesInfoCollection = $this->denormalizeValue($data['records'], ProfileInfoCollection::class);

        if (empty($data['marker'])) {
            return new GetListProfilesResponse(true, $profilesInfoCollection);
        }

        return new GetListProfilesResponse(true, $profilesInfoCollection, (int) $data['marker']);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return GetListProfilesResponse::class === $type;
    }
}
