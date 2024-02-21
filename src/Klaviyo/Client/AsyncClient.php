<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Klaviyo\Integration\Exception\JobRuntimeWarningException;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\TranslatorsRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Klaviyo\Client\Exception\TranslationException;
use Shopware\Core\Framework\Context;

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

    public function sendRequests(array $requests, Context $context = null): ClientResult
    {
        $this->clientResult = new ClientResult();
        $concurrency = 5;
        $pool = new Pool(
            $this->guzzleClient,
            $this->createAndSendRequests($requests, $context),
            [
                'concurrency' => $concurrency,
                'fulfilled' => function (Response $response, $index) {
                    if (isset($this->requests[$index])) {
                        $translator = $this->translatorsRegistry->getTranslatorForRequest($this->requests[$index]);

                        $translateResponseResult = $translator->translateResponse($response);

                        if ($translateResponseResult->isSuccess() === false) {
                            $orderId = $this->requests[$index]->getOrderId();

                            if ($translateResponseResult->getDetail() ===
                                'The phone number provided either does not exist or is ineligible to receive SMS') {
                                $exceptionType = new JobRuntimeWarningException(
                                    \sprintf(
                                        'Order[id: %s] error: %s',
                                        $orderId,
                                        $translateResponseResult->getDetail()
                                    )
                                );
                            } else {
                                $exceptionType = new \Exception(
                                    \sprintf(
                                        'Order[id: %s] error: %s',
                                        $orderId,
                                        $translateResponseResult->getDetail()
                                    )
                                );
                            }

                            $this->clientResult->addRequestError($this->requests[$index], $exceptionType);
                        } else {
                            $this->clientResult->addRequestResponse(
                                $this->requests[$index],
                                $translateResponseResult
                            );
                        }
                    }
                },
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

    protected function createAndSendRequests($requests, Context $context = null): \Generator
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
                $guzzleRequest = $translator->translateRequest($request, $context);

                yield function () use ($guzzleRequest, $guzzleRequestOptions, $translator) {
                    return $this->guzzleClient->sendAsync($guzzleRequest, $guzzleRequestOptions);
                };
            } catch (\Throwable $e) {
                $this->clientResult->addRequestError($request, $e);
            }
        }
    }
}
