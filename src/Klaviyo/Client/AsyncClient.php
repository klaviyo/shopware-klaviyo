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
use Klaviyo\Integration\Utils\Logger\ContextHelper;
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
                        $currentRequest = $this->requests[$index];
                        $translator = $this->translatorsRegistry->getTranslatorForRequest($currentRequest);

                        $translateResponseResult = $translator->translateResponse($response);

                        $this->clientResult->addRequestResponse($currentRequest, $translateResponseResult);

                        if (false === $translateResponseResult->isSuccess()) {
                            $errorDetail = $translateResponseResult->getErrorDetails();

                            if (\method_exists($currentRequest, 'getOrderId')) {
                                $orderId = $currentRequest->getOrderId();

                                if (
                                    (('The phone number provided either does not exist or is ineligible to receive SMS' ===
                                            $errorDetail)
                                        || (str_contains($errorDetail, 'Invalid phone number format')))
                                    ||
                                    (('Invalid email address' ===
                                            $errorDetail)
                                        || (str_contains($errorDetail, 'Invalid email address')))
                                ) {
                                    $exceptionType = new JobRuntimeWarningException(
                                        \sprintf('Order[id: %s] error: %s', $orderId, $errorDetail)
                                    );
                                } else {
                                    $throwText = \sprintf('Order[id: %s] error: %s', $orderId, $errorDetail);
                                    throw new TranslationException($response, $throwText);
                                }

                                $this->clientResult->addRequestError($currentRequest, $exceptionType);
                            } else if (\method_exists($currentRequest, 'getListId')) {
                                $listId = $currentRequest->getListId();

                                if ('Duplicate email subscribe found' === $errorDetail) {
                                    $exceptionType = new JobRuntimeWarningException(
                                        \sprintf('List[id: %s] error: %s', $listId, $errorDetail)
                                    );
                                } else {
                                    $throwText = \sprintf('List[id: %s] error: %s', $listId, $errorDetail);
                                    throw new TranslationException($response, $throwText);
                                }

                                $this->clientResult->addRequestError($currentRequest, $exceptionType);
                            } else {
                                $throwText = \sprintf('Event error: %s', $errorDetail);
                                throw new TranslationException($response, $throwText);
                            }
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
            RequestOptions::DELAY => 1000,
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
                $versionInfo = ContextHelper::fetchPluginVersion();
                $guzzleRequest = $guzzleRequest
                                    ->withHeader('X-Sw-Plugin-Version', $versionInfo['composer_version'])
                                    ->withHeader('X-Sw-Plugin-Version-db', $versionInfo['db_version'])
                                    ->withHeader('X-Sw-Version', ContextHelper::fetchShopwareVersion());

                yield function () use ($guzzleRequest, $guzzleRequestOptions) {
                    return $this->guzzleClient->sendAsync($guzzleRequest, $guzzleRequestOptions);
                };
            } catch (\Throwable $e) {
                $this->clientResult->addRequestError($request, $e);
            }
        }
    }
}
