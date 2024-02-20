<?php
declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;

class EventTrackingResponseDenormalizer extends AbstractDenormalizer
{
    /**
     * @throws DeserializationException
     */
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): EventTrackingResponse {
        $success = true;
        $detail = null;

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
            $detail = current($data['errors'])['detail'];
            $success = false;
        }

        return new EventTrackingResponse($success, $detail);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $type === EventTrackingResponse::class;
    }
}
