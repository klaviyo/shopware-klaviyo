<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\RemoveProfilesFromList\RemoveProfilesFromListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;

class RemoveProfilesFromListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public function translateRequest(object $request, Context $context = null): Request
    {
        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/profile-subscription-bulk-delete-jobs',
            $this->configuration->getGlobalNewEndpointUrl()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url, $body);
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint, $body): Request
    {
        return new Request(
            'POST',
            $endpoint,
            [
                'Authorization' => $this->configuration->getApiKey(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ],
            $body
        );
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
    private function assertStatusCode(ResponseInterface $response): void
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
