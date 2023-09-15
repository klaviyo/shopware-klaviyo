<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\StartedCheckoutEventTrackingRequest;

class StartedCheckoutEventTrackingRequestNormalizer extends AbstractNormalizer
{
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedData = $object->jsonSerialize();
        $normalizedData['token'] = $this->getToken();
        $normalizedData['time'] = $object->getTime()->getTimestamp();

        return $normalizedData;
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof StartedCheckoutEventTrackingRequest;
    }
}
