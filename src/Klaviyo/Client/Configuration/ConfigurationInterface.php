<?php

namespace Klaviyo\Integration\Klaviyo\Client\Configuration;

interface ConfigurationInterface
{
    public function getApiKey(): string;

    public function getPublicApiKey(): string;

    public function getTrackApiEndpointUrl(): string;

    public function getIdentifyApiEndpointUrl(): string;

    public function getListAndSegmentsApiEndpointUrl(): string;

    public function getRequestTimeout(): int;

    public function getConnectionTimeout(): int;

    public function getGlobalExclusionsAndUnsubscribes(): string;
}
