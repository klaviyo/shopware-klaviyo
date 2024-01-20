<?php

namespace Klaviyo\Integration\Klaviyo\Client\Configuration;

class Configuration implements ConfigurationInterface
{
    private string $apiKey;
    private string $publicApiKey;
    private string $trackApiEndpointUrl;
    private string $identifyApiEndpoint;
    private int $requestTimeout;
    private int $connectionTimeout;
    private string $globalNewEndpointUrl;
    private ?string $subscribersListId;

    public function __construct(
        string $apiKey,
        string $publicApiKey,
        string $trackApiEndpoint,
        string $identifyApiEndpoint,
        int $requestTimeout,
        int $connectionTimeout,
        string $globalNewEndpointUrl,
        ?string $subscribersListId
    ) {
        $this->apiKey = $apiKey;
        $this->publicApiKey = $publicApiKey;
        $this->trackApiEndpointUrl = $trackApiEndpoint;
        $this->identifyApiEndpoint = $identifyApiEndpoint;
        $this->requestTimeout = $requestTimeout;
        $this->connectionTimeout = $connectionTimeout;
        $this->globalNewEndpointUrl = $globalNewEndpointUrl;
        $this->subscribersListId = $subscribersListId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getPublicApiKey(): string
    {
        return $this->publicApiKey;
    }

    public function getTrackApiEndpointUrl(): string
    {
        return $this->trackApiEndpointUrl;
    }

    public function getIdentifyApiEndpointUrl(): string
    {
        return $this->identifyApiEndpoint;
    }

    public function getRequestTimeout(): int
    {
        return $this->requestTimeout;
    }

    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout;
    }

    public function getGlobalNewEndpointUrl(): string
    {
        return $this->globalNewEndpointUrl;
    }

    public function getSubscribersListId(): ?string
    {
        return $this->subscribersListId;
    }
}
