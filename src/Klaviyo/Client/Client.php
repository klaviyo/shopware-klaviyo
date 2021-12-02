<?php

namespace Klaviyo\Integration\Klaviyo\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\TranslatorsRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\Configuration;
use Klaviyo\Integration\Klaviyo\Client\Exception\ClientException;
use Klaviyo\Integration\Klaviyo\Client\Exception\EventTrackingOperationRequestFailedException;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;

class Client
{
    private TranslatorsRegistry $translatorsRegistry;
    private GuzzleClient $guzzleClient;
    private Configuration $configuration;

    public function __construct(
        TranslatorsRegistry $translatorsRegistry,
        GuzzleClient $guzzleClient,
        Configuration $configuration
    ) {
        $this->translatorsRegistry = $translatorsRegistry;
        $this->guzzleClient = $guzzleClient;
        $this->configuration = $configuration;
    }

    /**
     * @param object $request
     *
     * @throws ClientException
     * @throws \Throwable
     */
    public function sendRequest(object $request): object
    {
        $response = null;
        $guzzleRequest = null;

        try {
            $translator = $this->translatorsRegistry->getTranslatorForRequest($request);
            if (!$translator) {
                throw new TranslationException($request, 'Applicable translator for request DTO was not found');
            }

            $guzzleRequest = $translator->translateRequest($request);

            $options = [
                RequestOptions::CONNECT_TIMEOUT => $this->configuration->getConnectionTimeout(),
                RequestOptions::TIMEOUT => $this->configuration->getRequestTimeout(),
                RequestOptions::HTTP_ERRORS => false,
            ];
            $response = $this->guzzleClient->send($guzzleRequest, $options);

            return $translator->translateResponse($response);
        } catch (\Throwable $exception) {
            if ($exception instanceof ClientException) {
                $clientException = $exception;
            } else {
                $clientException = new EventTrackingOperationRequestFailedException(
                    sprintf('Klaviyo API request failed. Reason: %s', $exception->getMessage()),
                    $exception,
                    $guzzleRequest,
                    $response
                );
            }

            $clientException->addToLoggableContext('requestDTO', $request);

            throw $clientException;
        }
    }
}