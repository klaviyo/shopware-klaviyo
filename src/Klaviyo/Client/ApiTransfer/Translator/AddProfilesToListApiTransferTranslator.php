<?php

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\AddMembersToList\AddProfilesToListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;

class AddProfilesToListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param AddProfilesToListRequest $request
     */
    public function translateRequest(object $request, Context $context = null): Request
    {
        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/profile-bulk-import-jobs',
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
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
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

            return $this->deserialize($content, AddProfilesToListResponse::class);
        }

        $this->assertStatusCode($response);

        return new AddProfilesToListResponse(true);
    }

    /**
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TranslationException($response, \sprintf('Invalid response status code %s', $response->getStatusCode()));
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof AddProfilesToListRequest;
    }
}
