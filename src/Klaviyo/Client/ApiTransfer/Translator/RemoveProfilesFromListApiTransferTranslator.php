<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Psr\Http\Message\ResponseInterface;

class RemoveProfilesFromListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public function translateRequest(object $request): Request
    {
        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/list/%s/members?api_key=%s',
            $this->configuration->getListAndSegmentsApiEndpointUrl(),
            $request->getListId(),
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url, $body);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint, $body): Request
    {
        $guzzleRequest = new Request(
            'DELETE',
            $endpoint,
            [
                'Content-Type' => 'application/json'
            ],
            $body
        );

        return $guzzleRequest;
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();
            return $this->deserialize($content, RemoveProfilesFromListResponse::class);
        }

        $this->assertStatusCode($response);
        return new RemoveProfilesFromListResponse(true);
    }

    /**
     * @param ResponseInterface $response
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException($response, sprintf('Invalid response status code %s', $response->getStatusCode()));
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof RemoveProfilesFromListRequest;
    }
}
