<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\EventTracking\Common\EventTrackingResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Identify\IdentifyProfileRequest;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class IdentifyProfileRequestApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public function translateRequest(object $request): Request
    {
        $body = $this->serialize($request);

        return $this->constructGuzzleRequestToKlaviyoAPI($this->configuration->getIdentifyApiEndpointUrl(), $body);
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

    public function isSupport(object $request): bool
    {
        return $request instanceof IdentifyProfileRequest;
    }

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
