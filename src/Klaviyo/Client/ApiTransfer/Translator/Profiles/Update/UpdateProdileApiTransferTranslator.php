<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\Profiles\Update;

use GuzzleHttp\Psr7\Request;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileRequest;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\Profiles\Update\UpdateProfileResponse;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\AbstractApiTransferMessageTranslator;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Psr\Http\Message\ResponseInterface;

class UpdateProdileApiTransferTranslator extends AbstractApiTransferMessageTranslator
{
    /**
     * @param UpdateProfileRequest $request
     * @return Request
     */
    public function translateRequest(object $request): Request
    {
        $sensitiveFields = \array_filter([
            '$id' => $request->getCustomerProperties()->getId(),
            '$email' => $request->getCustomerProperties()->getEmail(),
            '$phone_number' => $request->getCustomerProperties()->getPhoneNumber(),
        ]);
        $propertiesParam = \implode('&', \array_map(function ($propName, $propValue) {
            return \sprintf('%s=%s', $propName, \urlencode($propValue));
        }, \array_keys($sensitiveFields), $sensitiveFields));

        $url = \sprintf(
            '%s/person/%s?%s&api_key=%s',
            $this->configuration->getGlobalExclusionsEndpointUrl(),
            $request->getProfileId(),
            $propertiesParam,
            $this->configuration->getApiKey()
        );

        return $this->constructGuzzleRequestToKlaviyoAPI($url);
    }

    public function translateResponse(ResponseInterface $response): object
    {
        $isJsonResponse = $this->isResponseJson($response);
        if ($isJsonResponse) {
            $content = $response->getBody()->getContents();
            $result = $this->deserialize($content, UpdateProfileResponse::class);

            return $result;
        }

        $this->assertStatusCode($response);
        // Throw different exception in case if response is 200 but not a json
        throw new TranslationException($response, 'Profile update API response expected to be a JSON');
    }

    private function assertStatusCode(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 200) {
            throw new TranslationException(
                $response,
                sprintf('Invalid profile update API response status code: %s', $response->getStatusCode())
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
            'PUT',
            $endpoint,
            [
                'Accept' => 'application/json',
            ]
        );
    }
}
