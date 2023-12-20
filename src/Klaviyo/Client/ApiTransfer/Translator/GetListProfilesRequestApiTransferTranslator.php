<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetListProfiles\GetListProfilesResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class GetListProfilesRequestApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param GetListProfilesRequest $request
     */
    public function translateRequest(object $request): Request
    {
        $url = \sprintf(
            '%s/segments/%s/profiles',
            $this->configuration->getGlobalNewEndpointUrl(),
            $request->getListId()
        );

        if ($request->getCursorMarker()) {
            $url .= "&marker={$request->getCursorMarker()}";
        }

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        $guzzleRequest = new Request(
            'GET',
            $endpoint,
            [
                'Authorization' => $this->configuration->getApiKey(),
                'Accept' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ]
        );

        return $guzzleRequest;
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();

            return $this->deserialize($content, GetListProfilesResponse::class);
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Get list profiles api response expected to be a JSON');
    }

    /**
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException($response, \sprintf('Invalid response status code %s', $response->getStatusCode()));
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof GetListProfilesRequest;
    }
}
