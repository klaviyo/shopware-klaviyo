<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify\IdentifyProfileRequest;

class IdentifyProfileRequestNormalizer extends AbstractNormalizer
{
    /**
     * @param IdentifyProfileRequest $object
     * @param string|null $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|null
     * @throws \Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $customerProperties = $this->normalizeObject($object->getCustomerProperties());

        return [
            'token' => $this->getPublicToken(),
            'properties' => $customerProperties
        ];
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof IdentifyProfileRequest;
    }
}
