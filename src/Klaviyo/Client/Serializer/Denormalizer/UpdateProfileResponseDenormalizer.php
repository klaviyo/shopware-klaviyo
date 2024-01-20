<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileResponse;

class UpdateProfileResponseDenormalizer extends AbstractDenormalizer
{
    public function denormalize($data, string $type, string $format = null, array $context = []): UpdateProfileResponse
    {
        $errors = $data['errors'][0] ?? '';

        if (!empty($errors['detail'])) {
            return new UpdateProfileResponse(true, $errors['detail']);
        }

        return new UpdateProfileResponse(true);
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return UpdateProfileResponse::class === $type;
    }
}
