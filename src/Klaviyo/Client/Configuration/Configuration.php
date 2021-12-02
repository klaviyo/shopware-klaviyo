<?php

namespace Klaviyo\Integration\Klaviyo\Client\Configuration;

class Configuration implements ConfigurationInterface
{
    private string $apiKey;
    private string $trackApiEndpointUrl;
    private string $listAndSegmentsApiEndpointUrl;
    private int $requestTimeout;
    private int $connectionTimeout;

    public function __construct(
        string $apiKey,
        string $trackApiEndpoint,
        string $listAndSegmentsApiEndpointUrl,
        int $requestTimeout,
        int $connectionTimeout
    ) {
        $this->apiKey = $apiKey;
        $this->trackApiEndpointUrl = $trackApiEndpoint;
        $this->listAndSegmentsApiEndpointUrl = $listAndSegmentsApiEndpointUrl;
        $this->requestTimeout = $requestTimeout;
        $this->connectionTimeout = $connectionTimeout;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getTrackApiEndpointUrl(): string
    {
        return $this->trackApiEndpointUrl;
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