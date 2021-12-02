<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class GetProfilesListsResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * {@inheritDoc}
     *
     * @return GetProfilesListsResponse
     * @throws DeserializationException
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $profilesListInfoCollection = new ProfilesListInfoCollection();

        if (!empty($data['detail']) || !empty($data['message'])) {
            $errorDetails = $data['message'] ?? $data['detail'];

            return new GetProfilesListsResponse(false, $profilesListInfoCollection, $errorDetails);
        }

        foreach ($data as $row) {
            $this->assertResultRow($row);
            $profilesListInfoCollection->add(new ProfilesListInfo($row['list_id'], $row['list_name']));
        }

        return new GetProfilesListsResponse(true, $profilesListInfoCollection);
    }

    private function assertResultRow($resultRow)
    {
        if (!is_array($resultRow)) {
            throw new DeserializationException('Each line in the profiles list response expected to be an array');
        }

        if (empty($resultRow['list_id'])) {
            throw new DeserializationException('Each line in the profiles list response expected to have a list_id');
        }

        if (empty($resultRow['list_name'])) {
            throw new DeserializationException('Each line in the profiles list response expected to have a list_name');
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === GetProfilesListsResponse::class;
    }
}