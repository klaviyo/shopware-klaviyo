<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\Profiles\Search;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdRequestInterface;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Search\GetProfileIdResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\AbstractApiTransferMessageTranslator;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class SearchProfileIdApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param GetProfileIdRequestInterface $request
     */
    public function translateRequest(object $request): Request
    {
        $url = \sprintf(
            '%s/people/search?%s=%s&api_key=%s',
            $this->configuration->getGlobalNewEndpointUrl(),
            $request->getSearchFieldName(),
            \urlencode($request->getSearchFieldValue()),
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();
            $result = $this->deserialize($content, GetProfileIdResponse::class);

            return $result;
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Profile search API response expected to be a JSON');
    }

    private function assertStatusCode(ResponseInterface $response)
    {
        if (200 !== $response->getStatusCode() || 404 !== $response->getStatusCode()) {
            throw new TranslationException($response, \sprintf('Invalid profile search API response status code: %s', $response->getStatusCode()));
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof GetProfileIdRequestInterface;
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        return new Request(
            'GET',
            $endpoint,
            [
                'Authorization' => $this->configuration->getApiKey(),
                'Accept' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ]
        );
    }
}
