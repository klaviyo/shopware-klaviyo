<?php

namespace Klaviyo\Integration\Klaviyo\Client\Configuration;

class Configuration implements ConfigurationInterface
{
    private string $apiKey;
    private string $publicApiKey;
    private string $trackApiEndpointUrl;
    private string $identifyApiEndpoint;
    private string $listAndSegmentsApiEndpointUrl;
    private int $requestTimeout;
    private int $connectionTimeout;

    public function __construct(
        string $apiKey,
        string $publicApiKey,
        string $trackApiEndpoint,
        string $identifyApiEndpoint,
        string $listAndSegmentsApiEndpointUrl,
        int $requestTimeout,
        int $connectionTimeout
    ) {
        $this->apiKey = $apiKey;
        $this->publicApiKey = $publicApiKey;
        $this->trackApiEndpointUrl = $trackApiEndpoint;
        $this->identifyApiEndpoint = $identifyApiEndpoint;
        $this->listAndSegmentsApiEndpointUrl = $listAndSegmentsApiEndpointUrl;
        $this->requestTimeout = $requestTimeout;
        $this->connectionTimeout = $connectionTimeout;
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

    public function getListAndSegmentsApiEndpointUrl(): string
    {
        return $this->listAndSegmentsApiEndpointUrl;
    }

    public function getRequestTimeout(): int
    {
        return $this->requestTimeout;
    }

    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout;
    }
}
