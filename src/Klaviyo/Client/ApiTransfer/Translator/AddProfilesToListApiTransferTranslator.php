<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Psr\Http\Message\ResponseInterface;

class AddProfilesToListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param AddProfilesToListRequest $request
     *
     * @return Request
     */
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
            'POST',
            $endpoint,
            [
                'Accept' => 'application/json',
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
            $result = $this->deserialize($content, AddProfilesToListResponse::class);

            return $result;
        }

        $this->assertStatusCode($response);

        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Add Profiles to list api response expected to be a JSON');
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

    public function isSupport(object $request): bool
    {
        return $request instanceof AddProfilesToListRequest;
    }
}
