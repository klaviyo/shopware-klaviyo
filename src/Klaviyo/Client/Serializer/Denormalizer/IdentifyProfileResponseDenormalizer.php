<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify\IdentifyProfileResponse;

class IdentifyProfileResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize(
        $data,
        string $type,
        string $format = null,
        array $context = []
    ): IdentifyProfileResponse {
        $errors = $data['errors'][0] ?? '';

        if (!empty($errors['detail'])) {
            return new IdentifyProfileResponse(false, $errors['detail']);
        }

        return new IdentifyProfileResponse(true);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return IdentifyProfileResponse::class === $type;
    }
}
