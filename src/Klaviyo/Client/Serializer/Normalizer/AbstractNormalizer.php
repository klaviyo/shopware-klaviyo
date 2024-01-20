<?php

namespace Klaviyo\Integration\Klaviyo\Client\Serializer\Normalizer;

use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\SerializationException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class AbstractNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    private ConfigurationInterface $configuration;
    private NormalizerInterface $normalizerVirtualProxy;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    protected function getToken(): string
    {
        return $this->configuration->getApiKey();
    }

    protected function getPublicToken(): string
    {
        return $this->configuration->getPublicApiKey();
    }

    /**
     * @throws ExceptionInterface
     * @throws SerializationException
     */
    protected function normalizeObject(?object $object): ?array
    {
        if (!$object) {
            return null;
        }

        if (!$this->normalizerVirtualProxy->supportsNormalization($object)) {
            throw new SerializationException(
                \sprintf('Could not normalize %s, normalizer not found', get_class($object))
            );
        }

        return $this->normalizerVirtualProxy->normalize($object);
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizerVirtualProxy = $normalizer;
    }
}
