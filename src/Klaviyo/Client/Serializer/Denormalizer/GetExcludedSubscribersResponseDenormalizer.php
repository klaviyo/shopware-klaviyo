<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers\Response;
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
    ): Response {
        $emails = [];

        if (empty($data)) {
            throw new DeserializationException(
                'For some reason, the data in the response from Klaviyo was not received'
            );
        }

        if (!is_array($data) && (!isset($data['data']) || !isset($data['errors']))) {
            throw new DeserializationException(
                'For some reason, the data in the response from Klaviyo was not correct structure'
            );
        }

        if (!empty($data['errors'][0]['code'])) {
            throw new DeserializationException(current($data['errors'])['detail']);
        }

        foreach ($data['data'] as $row) {
            $this->assertResultRow($row);
            $emails[] = $row['attributes']['email'];
        }

        $nextPageLink = $data['links']['next'] ?? null;

        return new Response($emails, $nextPageLink);
    }

    /**
     * @throws DeserializationException
     */
    private function assertResultRow($resultRow): void
    {
        if (!is_array($resultRow)) {
            throw new DeserializationException(
                'Each line in the excluded subscribers list response expected to be an array'
            );
        }

        if (empty($resultRow['attributes']['email'])) {
            throw new DeserializationException(
                'Each line in the excluded subscribers list response expected to have a email'
            );
        }
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $type === Response::class;
    }
}
