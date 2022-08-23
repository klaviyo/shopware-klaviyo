<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\Channel;

use Klaviyo\Integration\Configuration\ConfigurationInterface;
use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Exception\InvalidConfigurationException;

class GetValidChannelConfig
{
    private ConfigurationRegistry $configurationRegistry;

    public function __construct(ConfigurationRegistry $configurationRegistry)
    {
        $this->configurationRegistry = $configurationRegistry;
    }

    public function execute(string $channelId): ?ConfigurationInterface
    {
        try {
            $configuration = $this->configurationRegistry->getConfiguration($channelId);

            return $configuration->isAccountEnabled() ? $configuration : null;
        } catch (InvalidConfigurationException $e) {
            return null;
        }
    }
}
