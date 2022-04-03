<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribersRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribersResponse;
use Psr\Http\Message\ResponseInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;

class GetExcludedSubscribersApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public function translateRequest(object $request): Request
    {
        $url = sprintf(
            '%s/people/exclusions?count=%s&page=%s&api_key=%s',
            $this->configuration->getGlobalExclusionsAndUnsubscribes(),
            $request->getCount(),
            $request->getPage(),
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        return new Request(
            'GET',
            $endpoint,
            [
                'Accept' => 'application/json'
            ]
        );
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();

            return $this->deserialize($content, GetExcludedSubscribersResponse::class);
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Get excluded subscribers api response expected to be a JSON');
    }

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