<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Klaviyo\Client\Configuration\{Configuration, ConfigurationInterface};

class ClientConfigurationFactory
{
    private const TRACKING_ENDPOINT_URL = 'https://a.klaviyo.com/api/track';
    private const IDENTIFY_ENDPOINT_URL = 'https://a.klaviyo.com/api/identify';
    private const LIST_AND_SEGMENTS_API_ROOT_ENDPOINT_URL = 'https://a.klaviyo.com/api/v2';
    private const GLOBAL_EXCLUSIONS_ENDPOINT_URL = 'https://a.klaviyo.com/api/v1';
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

        return new Configuration(
            $pluginConfiguration->getPrivateApiKey(),
            $pluginConfiguration->getPublicApiKey(),
            self::TRACKING_ENDPOINT_URL,
            self::IDENTIFY_ENDPOINT_URL,
            self::LIST_AND_SEGMENTS_API_ROOT_ENDPOINT_URL,
            self::REQUEST_TIMEOUT,
            self::CONNECTION_TIMEOUT,
            self::GLOBAL_EXCLUSIONS_ENDPOINT_URL
        );
    }
}
