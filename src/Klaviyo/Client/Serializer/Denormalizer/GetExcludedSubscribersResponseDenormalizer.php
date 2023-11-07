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

        if (!is_array($data) || !isset($data['data'])) {
            throw new DeserializationException(
                'For some reason, the data in the response from Klaviyo was not correct structure'
            );
        }

        foreach ($data['data'] as $row) {
            $this->assertResultRow($row);
            $emails[] = $row['email'];
        }

        $page = isset($data['page']) ? $data['page'] : 0;
        $total = isset($data['total']) ? $data['total'] : count($emails);

        return new Response(
            $emails,
            (int)$page,
            (int)$total
        );
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
        return $type === Response::class;
    }
}
