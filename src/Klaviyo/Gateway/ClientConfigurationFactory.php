<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\{Configuration, ConfigurationInterface};

class ClientConfigurationFactory
{
    public const AUTHORIZATION_PREKEY = 'Klaviyo-API-Key';
    public const API_REVISION_DATE = '2023-12-15';
    private const TRACKING_ENDPOINT_URL = 'https://a.klaviyo.com/api/events';
    private const IDENTIFY_ENDPOINT_URL = 'https://a.klaviyo.com/api/profiles';
    private const GLOBAL_NEW_ENDPOINT_URL = 'https://a.klaviyo.com/api';
    private const REQUEST_TIMEOUT = 30;
    private const CONNECTION_TIMEOUT = 15;

    private ConfigurationRegistry $pluginConfigurationRegistry;

    public function __construct(ConfigurationRegistry $pluginConfigurationRegistry)
    {
        $this->pluginConfigurationRegistry = $pluginConfigurationRegistry;
    }

    public function create(string $channelId): ConfigurationInterface
    {
        $pluginConfiguration = $this->pluginConfigurationRegistry->getConfiguration($channelId);
        $subscribersListId = $pluginConfiguration->getSubscribersListId();

        return $this->createByKeys(
            $pluginConfiguration->getPrivateApiKey(),
            $pluginConfiguration->getPublicApiKey(),
            $subscribersListId
        );
    }

    public function createByKeys(
        string $privateKey,
        string $publicKey,
        string $subscribersListId = null
    ): ConfigurationInterface {
        return new Configuration(
            self::AUTHORIZATION_PREKEY . ' ' . $privateKey,
            $publicKey,
            self::TRACKING_ENDPOINT_URL,
            self::IDENTIFY_ENDPOINT_URL,
            self::REQUEST_TIMEOUT,
            self::CONNECTION_TIMEOUT,
            self::GLOBAL_NEW_ENDPOINT_URL,
            $subscribersListId
        );
    }
}
