<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\StartedCheckoutEventTrackingRequest;

class StartedCheckoutEventTrackingRequestNormalizer extends AbstractNormalizer
{
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return $object->jsonSerialize();
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof StartedCheckoutEventTrackingRequest;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            StartedCheckoutEventTrackingRequest::class => true,
        ];
    }
}
