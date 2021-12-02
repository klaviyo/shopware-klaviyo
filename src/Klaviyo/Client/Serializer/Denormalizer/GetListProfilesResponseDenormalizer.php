<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Common\ProfileInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class GetListProfilesResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritDoc}
     *
     * @return GetListProfilesResponse
     * @throws DeserializationException
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $profilesInfoCollection = new ProfileInfoCollection();

        if (!empty($data['detail']) || !empty($data['message'])) {
            $errorDetails = $data['message'] ?? $data['detail'];

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

        return new GetListProfilesResponse(true, $profilesInfoCollection, (int)$data['marker']);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === GetListProfilesResponse::class;
    }
}