<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileResponse;

class UpdateProfileResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $errorDetail = $data['detail'] ?? '';
        if ($errorDetail) {
            return new UpdateProfileResponse(true, $errorDetail);
        }

        return new UpdateProfileResponse(true);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === UpdateProfileResponse::class;
    }
}
