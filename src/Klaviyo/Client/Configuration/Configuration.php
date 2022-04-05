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
    private string $globalExclusionsEndpointUrl;

    public function __construct(
        string $apiKey,
        string $publicApiKey,
        string $trackApiEndpoint,
        string $identifyApiEndpoint,
        string $listAndSegmentsApiEndpointUrl,
        int $requestTimeout,
        int $connectionTimeout,
        string $globalExclusionsEndpointUrl
    ) {
        $this->apiKey = $apiKey;
        $this->publicApiKey = $publicApiKey;
        $this->trackApiEndpointUrl = $trackApiEndpoint;
        $this->identifyApiEndpoint = $identifyApiEndpoint;
        $this->listAndSegmentsApiEndpointUrl = $listAndSegmentsApiEndpointUrl;
        $this->requestTimeout = $requestTimeout;
        $this->connectionTimeout = $connectionTimeout;
        $this->globalExclusionsEndpointUrl = $globalExclusionsEndpointUrl;
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

    public function getGlobalExclusionsEndpointUrl(): string
    {
        return $this->globalExclusionsEndpointUrl;
    }
}
