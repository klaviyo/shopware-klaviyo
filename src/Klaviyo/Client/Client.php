<?php

namespace Klaviyo\Integration\Klaviyo\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\TranslatorsRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\ClientException;
use Klaviyo\Integration\Klaviyo\Client\Exception\EventTrackingOperationRequestFailedException;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;

class Client implements ClientInterface
{
    private TranslatorsRegistry $translatorsRegistry;
    private GuzzleClient $guzzleClient;
    private ConfigurationInterface $configuration;

    public function __construct(
        TranslatorsRegistry $translatorsRegistry,
        GuzzleClient $guzzleClient,
        ConfigurationInterface $configuration
    ) {
        $this->translatorsRegistry = $translatorsRegistry;
        $this->guzzleClient = $guzzleClient;
        $this->configuration = $configuration;
    }

    public function sendRequests(array $requests): ClientResult
    {
        $clientResult = new ClientResult();
        $guzzleRequestOptions = [
            RequestOptions::CONNECT_TIMEOUT => $this->configuration->getConnectionTimeout(),
            RequestOptions::TIMEOUT => $this->configuration->getRequestTimeout(),
            RequestOptions::HTTP_ERRORS => false,
        ];

        foreach ($requests as $request) {
            $response = null;
            $guzzleRequest = null;

            try {
                $translator = $this->translatorsRegistry->getTranslatorForRequest($request);
                if (!$translator) {
                    throw new TranslationException($request, 'Applicable translator for request DTO was not found');
                }

                $guzzleRequest = $translator->translateRequest($request);
                $this->guzzleClient->send($guzzleRequest, $guzzleRequestOptions);
            } catch (ClientException $exception) {
                $exception->addToLoggableContext('requestDTO', $request);
                $clientResult->addRequestError($request, $exception);
            } catch (\Throwable $exception) {
                $clientException = new EventTrackingOperationRequestFailedException(
                    sprintf('Klaviyo API request failed. Reason: %s', $exception->getMessage()),
                    $exception,
                    $guzzleRequest,
                    $response
                );

                $clientException->addToLoggableContext('requestDTO', $request);
                $clientResult->addRequestError($request, $clientException);
            }
        }

        return $clientResult;
    }
}
