<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Psr\Http\Message\ResponseInterface;

class RealtimeProfilesSubscribeToListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param object $request
     * @return Request
     */
    public function translateRequest(object $request): Request
    {
        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/list/%s/subscribe?api_key=%s',
            $this->configuration->getListAndSegmentsApiEndpointUrl(),
            $request->getListId(),
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url, $body);
    }

    /**
     * @throws TranslationException
     * @throws DeserializationException
     */
    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();

            return $this->deserialize($content, SubscribeToListResponse::class);
        }

        $this->assertStatusCode($response);

        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Add real-time Profiles to list api response expected to be a JSON');
    }

    /**
     * @param object $request
     * @return bool
     */
    public function isSupport(object $request): bool
    {
        return $request instanceof SubscribeToListRequest;
    }

    /**
     * @param string $endpoint
     * @param $body
     * @return Request
     */
    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint, $body): Request
    {
        return new Request(
            'POST',
            $endpoint,
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            $body
        );
    }

    /**
     * @param ResponseInterface $response
     * @return void
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException($response, \sprintf('Invalid response status code %s', $response->getStatusCode()));
        }
    }
}
