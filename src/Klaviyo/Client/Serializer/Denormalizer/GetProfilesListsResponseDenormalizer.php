<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfo;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\DTO\ProfilesListInfoCollection;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class GetProfilesListsResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @throws DeserializationException
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): GetProfilesListsResponse {
        $profilesListInfoCollection = new ProfilesListInfoCollection();
        $nextPageUrl = '';

        if (!empty($data['errors'][0]) && !empty($data['errors'][0]['detail'])) {
            $errorDetails = $data['errors'][0]['detail'];

            return new GetProfilesListsResponse(false, $profilesListInfoCollection, $nextPageUrl, $errorDetails);
        }

        if (!isset($data['data']) && !isset($data['links'])) {
            throw new DeserializationException('There is something wrong with the response structure of the list data');
        }

        foreach ($data['data'] as $row) {
            $this->assertResultRow($row);
            $profilesListInfoCollection->add(new ProfilesListInfo($row['id'], $row['attributes']['name']));
        }

        if (!empty($data['links']['next'])) {
            $nextPageUrl = $data['links']['next'];
        }

        return new GetProfilesListsResponse(true, $profilesListInfoCollection, $nextPageUrl);
    }

    /**
     * @throws DeserializationException
     */
    private function assertResultRow($resultRow): void
    {
        if (!is_array($resultRow)) {
            throw new DeserializationException('Each line in the profiles list response expected to be an array');
        }

        if (empty($resultRow['id'])) {
            throw new DeserializationException('Each line in the profiles list response expected to have an ID');
        }

        if (empty($resultRow['attributes']['name'])) {
            throw new DeserializationException('Each line in the profiles list response expected to have a Name');
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return GetProfilesListsResponse::class === $type;
    }
}
