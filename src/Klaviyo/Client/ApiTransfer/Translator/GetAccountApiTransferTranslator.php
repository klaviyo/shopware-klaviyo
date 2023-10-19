<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account\GetAccountRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Account\GetAccountResponse;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class GetAccountApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    public const PERMISSION_DENIED = 'permission_denied';

    public function translateRequest(object $request): Request
    {
        $url = \sprintf(
            '%s/accounts/%s/',
            $this->configuration->getGlobalNewEndpointUrl(),
            $request->getAccountId()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);

        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();
            $deserializeResult = $this->deserialize($content, GetAccountResponse::class);

            if (self::PERMISSION_DENIED === $deserializeResult->getCode()) {
                $responsePublicKey = $response->getHeader('CID');

                if (in_array($this->configuration->getPublicApiKey(), $responsePublicKey)) {
                    $deserializeResult = new GetAccountResponse(true, $this->configuration->getPublicApiKey());
                } else {
                    $deserializeResult = new GetAccountResponse(
                        false,
                        $this->configuration->getPublicApiKey(),
                        '',
                        'Invalid Account ID'
                    );
                }
            }

            return $deserializeResult;
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Get account api response expected to be a JSON');
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof GetAccountRequest;
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        $authorizationValue = ClientConfigurationFactory::AUTHORIZATION_PREKEY . ' ' .
            $this->configuration->getApiKey();

        return new Request(
            'GET',
            $endpoint,
            [
                'Authorization' => $authorizationValue,
                'Accept' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ]
        );
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
}
