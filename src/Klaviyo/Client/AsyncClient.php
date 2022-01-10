<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\ApiTransferMessageTranslatorInterface;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\TranslatorsRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;

class AsyncClient implements ClientInterface
{
    private TranslatorsRegistry $translatorsRegistry;
    private GuzzleClient $guzzleClient;
    private ConfigurationInterface $configuration;

    private array $requests = [];
    private int $requestIndex = 0;
    private ClientResult $clientResult;

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
        $this->clientResult = new ClientResult();
        $concurrency = 5;
        $pool = new Pool(
            $this->guzzleClient,
            $this->createAndSendRequests($requests),
            [
                'concurrency' => $concurrency,
                'rejected' => function (TransferException $reason, $index) {
                    if (isset($this->requests[$index])) {
                        $this->clientResult->addRequestError($this->requests[$index], $reason);
                    }
                },
            ]
        );

        $pool->promise()->wait();
        $this->requests = [];
        $this->requestIndex = 0;

        return $this->clientResult;
    }

    protected function createAndSendRequests($requests): \Generator
    {
        $guzzleRequestOptions = [
            RequestOptions::CONNECT_TIMEOUT => $this->configuration->getConnectionTimeout(),
            RequestOptions::TIMEOUT => $this->configuration->getRequestTimeout(),
            RequestOptions::HTTP_ERRORS => false,
        ];

        foreach ($requests as $request) {
            $translator = $this->translatorsRegistry->getTranslatorForRequest($request);

            if (!$translator) {
                $this->clientResult->addRequestError(
                    $request,
                    new TranslationException($request, 'Applicable translator for request DTO was not found')
                );
                continue;
            }

            $this->requests[$this->requestIndex++] = $request;

            try {
                $guzzleRequest = $translator->translateRequest($request);

                yield function () use ($guzzleRequest, $guzzleRequestOptions, $translator) {
                    return $this->guzzleClient->sendAsync($guzzleRequest, $guzzleRequestOptions);
                };
            } catch (\Throwable $e) {
                $this->clientResult->addRequestError($request, $e);
            }
        }
    }
}
