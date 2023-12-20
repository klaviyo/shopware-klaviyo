<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingResponse;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class GenericEventTrackingApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    private string $requestClassName;

    public function __construct(
        SerializerInterface $serializer,
        ConfigurationInterface $configuration,
        string $requestClassName
    ) {
        $this->requestClassName = $requestClassName;

        parent::__construct($serializer, $configuration);
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof $this->requestClassName;
    }

    public function translateRequest(object $request): Request
    {
        $body = $this->serialize($request);

        return $this->constructGuzzleRequestToKlaviyoAPI($this->configuration->getTrackApiEndpointUrl(), $body);
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $this->assertStatusCode($response);

        $content = $response->getBody()->getContents();
        $isSuccess = trim($content) === '1';

        return new EventTrackingResponse($isSuccess);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint, $body): Request
    {
        return new Request(
            'POST',
            $endpoint,
            [
                'Authorization' => $this->configuration->getApiKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ],
            \sprintf('data=%s', $body)
        );
    }

    /**
     * @param ResponseInterface $response
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException(
                $response,
                \sprintf('Invalid response status code %s', $response->getStatusCode())
            );
        }
    }
}
