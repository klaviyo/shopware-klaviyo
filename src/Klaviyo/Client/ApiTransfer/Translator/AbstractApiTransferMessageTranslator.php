<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractApiTransferMessageTranslator implements ApiTransferMessageTranslatorInterface
{
    private SerializerInterface $serializer;
    protected ConfigurationInterface $configuration;

    public function __construct(SerializerInterface $serializer, ConfigurationInterface $configuration)
    {
        $this->serializer = $serializer;
        $this->configuration = $configuration;
    }

    protected function serialize(object $object): string
    {
        return $this->serializer->serialize($object, JsonEncoder::FORMAT);
    }

    protected function deserialize(string $valueForDeserialization, string $class): object
    {
        $result = $this->serializer->deserialize($valueForDeserialization, $class, JsonEncoder::FORMAT);
        if (!$result instanceof $class) {
            throw new DeserializationException(
                \sprintf(
                    'Failed to deserialize string into instance of class %s. "%s" received after deserialization',
                    $class,
                    is_object($result) ? get_class($result) : gettype($result)
                )
            );
        }

        return $result;
    }

    protected function isResponseJson(ResponseInterface $response): bool
    {
        $responseContentTypes = $response->getHeader('Content-Type');
        $isJsonResponse = false;
        foreach ($responseContentTypes as $responseContentType) {
            if (
                (false !== stripos($responseContentType, 'application/json'))
                || (false !== stripos($responseContentType, 'application/vnd.api+json'))
            ) {
                $isJsonResponse = true;
            }
        }

        return $isJsonResponse;
    }
}
