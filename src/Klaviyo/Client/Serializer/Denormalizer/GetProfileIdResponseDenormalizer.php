<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdResponse;

class GetProfileIdResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = []): GetProfileIdResponse
    {
        $isProfileNotExists = ($data['data'] ?? '') === 'There is no profile matching the given parameters.';

        if ($isProfileNotExists) {
            return new GetProfileIdResponse(true);
        }

        if (!empty($data['errors'])) {
            $errorDetail = !empty($data['errors'][0]['detail']) ?
                $data['errors'][0]['detail'] : 'Invalid API response: "id" field is missing';

            return new GetProfileIdResponse(false, null, $errorDetail);
        }

        if (!empty($data['data'][0]['id'])) {
            $klaviyoProfileId = $data['data'][0]['id'];
        } else {
            $klaviyoProfileId = null;
        }

        return new GetProfileIdResponse(true, $klaviyoProfileId, null);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $type === GetProfileIdResponse::class;
    }
}
