<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\RealSubscribersToKlaviyoRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\SubscribeCustomersToList\SubscribeToListResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\DeserializationException;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;
use Shopware\Core\Framework\Context;

class RealtimeProfilesSubscribeToListApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param object $request
     * @return Request
     */
    public function translateRequest(object $request, Context $context = null): Request
    {
        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/profile-subscription-bulk-create-jobs',
            $this->configuration->getGlobalNewEndpointUrl()
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

        return new SubscribeToListResponse(true);
    }

    /**
     * @param object $request
     * @return bool
     */
    public function isSupport(object $request): bool
    {
        return $request instanceof RealSubscribersToKlaviyoRequest;
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
                'Authorization' => $this->configuration->getApiKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
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
