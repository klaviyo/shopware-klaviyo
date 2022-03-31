<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\{
    //TODO move to different folders
    ExcludedSubscribers,
    ExcludedSubscribersCollection,
    GetExcludedSubscribersResponse
};
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class GetExcludedSubscribersResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @throws DeserializationException
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): GetExcludedSubscribersResponse {
        $excludedSubscribersCollection = new ExcludedSubscribersCollection();
        foreach ($data['data'] as $row) {
            $this->assertResultRow($row);
            $excludedSubscribersCollection->add(new ExcludedSubscribers($row['email']));
        }

        return new GetExcludedSubscribersResponse($excludedSubscribersCollection, (string) $data['page']);
    }

    /**
     * @throws DeserializationException
     */
    private function assertResultRow($resultRow)
    {
        if (!is_array($resultRow)) {
            throw new DeserializationException(
                'Each line in the excluded subscribers list response expected to be an array'
            );
        }

        if (empty($resultRow['email'])) {
            throw new DeserializationException(
                'Each line in the excluded subscribers list response expected to have a email'
            );
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $type === GetExcludedSubscribersResponse::class;
    }
}