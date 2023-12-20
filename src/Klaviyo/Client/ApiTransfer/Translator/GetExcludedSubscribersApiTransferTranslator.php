<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribers;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class GetExcludedSubscribersApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param GetExcludedSubscribers\Request $request
     */
    public function translateRequest(object $request): Request
    {
        $url = \sprintf(
            '%s/people/exclusions?count=%s&page=%s',
            $this->configuration->getGlobalNewEndpointUrl(),
            $request->getCount(),
            $request->getPage()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
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

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();

            return $this->deserialize($content, GetExcludedSubscribers\Response::class);
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Get excluded subscribers api response expected to be a JSON');
    }

    private function assertStatusCode(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException(
                $response,
                \sprintf('Invalid response status code %s', $response->getStatusCode())
            );
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof GetExcludedSubscribers\Request;
    }
}
