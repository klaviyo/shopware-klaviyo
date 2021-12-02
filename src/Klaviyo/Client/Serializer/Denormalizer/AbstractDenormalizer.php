<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Denormalizer;

use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

abstract class AbstractDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    private DenormalizerInterface $denormalizerVirtualProxy;

    /**
     * @param mixed $value
     * @param string $type
     *
     * @return mixed
     * @throws DeserializationException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function denormalizeValue($value, string $type)
    {
        if (!$this->denormalizerVirtualProxy->supportsDenormalization($value, $type)) {
            throw new DeserializationException(
                sprintf(
                    'Could not denormalize %s, denormalizer not found',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        return $this->denormalizerVirtualProxy->denormalize($value, $type);
    }

    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizerVirtualProxy = $denormalizer;
    }
}