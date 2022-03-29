<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribersRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\GetLists\GetProfilesListsResponse;
use Psr\Http\Message\ResponseInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;

class GetExcludedSubscribersApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public function translateRequest(object $request): Request
    {
        $url = sprintf(
            '%s/people/exclusions?api_key=%s',
            $this->configuration->getListAndSegmentsApiEndpointUrl(),
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        $guzzleRequest = new Request(
            'GET',
            $endpoint,
            [
                'Accept' => 'application/json'
            ]
        );

        return $guzzleRequest;
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();
            $result = $this->deserialize($content, GetProfilesListsResponse::class);

            return $result;
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Get profiles lists api response expected to be a JSON');
    }

    /**
     * @param ResponseInterface $response
     *
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException(
                $response,
                sprintf('Invalid response status code %s', $response->getStatusCode())
            );
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof GetExcludedSubscribersRequest;
    }
}