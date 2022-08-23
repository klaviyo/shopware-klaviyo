<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdResponse;

class GetProfileIdResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $isProfileNotExists = ($data['detail'] ?? '') === 'There is no profile matching the given parameters.';
        if ($isProfileNotExists) {
            return new GetProfileIdResponse(true);
        }

        if (!empty($data['detail']) || empty($data['id'])) {
            $errorDetail = !empty($data['detail']) ? $data['detail'] : 'Invalid API response: "id" field is missing';

            return new GetProfileIdResponse(false, null, $errorDetail);
        }

        return new GetProfileIdResponse(true, $data['id'], null);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === GetProfileIdResponse::class;
    }
}
