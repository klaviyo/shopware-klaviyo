<?php

namespace Klaviyo\Integration\Configuration;

class ConfigurationRegistry
{
    private ConfigurationFactoryInterface $configurationFactory;

    /**
     * @var ConfigurationInterface[]
     */
    private array $configurationPerSalesChannel = [];

    public function __construct(ConfigurationFactoryInterface $configurationFactory)
    {
        $this->configurationFactory = $configurationFactory;
    }

    public function getConfiguration(string $channelId): ConfigurationInterface
    {
        if (!isset($this->configurationPerSalesChannel[$channelId])) {
            $this->configurationPerSalesChannel[$channelId] = $this->configurationFactory
                ->create($channelId);
        }

        return $this->configurationPerSalesChannel[$channelId];
    }
}
