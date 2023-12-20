<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\Profiles\Update;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\AbstractApiTransferMessageTranslator;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Klaviyo\Integration\Klaviyo\Gateway\ClientConfigurationFactory;
use Psr\Http\Message\ResponseInterface;

class UpdateProfileApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param UpdateProfileRequest $request
     */
    public function translateRequest(object $request): Request
    {
        $sensitiveFields = \array_filter([
            '$id' => $request->getCustomerProperties()->getId(),
            '$email' => $request->getCustomerProperties()->getEmail(),
            '$phone_number' => $request->getCustomerProperties()->getPhoneNumber(),
        ]);
        /*$propertiesParam = \implode('&', \array_map(function ($propName, $propValue) {
            return \sprintf('%s=%s', $propName, \urlencode($propValue));
        }, \array_keys($sensitiveFields), $sensitiveFields));*/

        $body = $this->serialize($request);

        $url = \sprintf(
            '%s/person/%s?%s&api_key=%s',
            $this->configuration->getGlobalExclusionsEndpointUrl(),
            $request->getProfileId(),
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();

            return $this->deserialize($content, UpdateProfileResponse::class);
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Profile update API response expected to be a JSON');
    }

    /**
     * @throws TranslationException
     */
    private function assertStatusCode(ResponseInterface $response): void
    {
        if (200 !== $response->getStatusCode()) {
            throw new TranslationException(
                $response,
                \sprintf('Invalid profile update API response status code: %s', $response->getStatusCode())
            );
        }
    }

    public function isSupport(object $request): bool
    {
        return $request instanceof UpdateProfileRequest;
    }

    private function constructGuzzleRequestToKlaviyoAPI(string $endpoint): Request
    {
        return new Request(
            'POST',
            $endpoint,
            [
                'Authorization' => $this->configuration->getApiKey(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'revision' => ClientConfigurationFactory::API_REVISION_DATE,
            ]
        );
    }
}
