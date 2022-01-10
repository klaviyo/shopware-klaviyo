<?php

namespace Klaviyo\Integration\Configuration;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ConfigurationRegistry
{
    private ConfigurationFactoryInterface $configurationFactory;

    /**
     * @var array|ConfigurationInterface
     */
    private array $configurationPerSalesChannel = [];

    private ?ConfigurationInterface $defaultConfiguration = null;

    public function __construct(ConfigurationFactoryInterface $configurationFactory)
    {
        $this->configurationFactory = $configurationFactory;
    }

    public function getConfigurationByChannelId(string $channelId): ConfigurationInterface
    {
        if (!isset($this->configurationPerSalesChannel[$channelId])) {
            $this->configurationPerSalesChannel[$channelId] = $this->configurationFactory
                ->create($channelId);
        }

        return $this->configurationPerSalesChannel[$channelId];
    }

    // TODO: remove later
    public function getConfiguration(SalesChannelEntity $salesChannelEntity): ConfigurationInterface
    {
        if (!isset($this->configurationPerSalesChannel[$salesChannelEntity->getId()])) {
            $this->configurationPerSalesChannel[$salesChannelEntity->getId()] = $this->configurationFactory
                ->create($salesChannelEntity);
        }

        return $this->configurationPerSalesChannel[$salesChannelEntity->getId()];
    }

    public function getDefaultConfiguration(): ConfigurationInterface
    {
        if (!$this->defaultConfiguration) {
            $this->defaultConfiguration = $this->configurationFactory->create(null);
        }

        return $this->defaultConfiguration;
    }
}
