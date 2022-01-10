<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\ClientFactory;
use Klaviyo\Integration\Klaviyo\Client\ClientInterface;

class ClientRegistry
{
    /**
     * @var ClientInterface[]
     */
    private array $clientPerSalesChannelHashMap = [];

    private ClientFactory $clientFactory;
    private ClientConfigurationFactory $clientConfigurationFactory;

    public function __construct(
        ClientFactory $clientFactory,
        ClientConfigurationFactory $clientConfigurationFactory
    ) {
        $this->clientFactory = $clientFactory;
        $this->clientConfigurationFactory = $clientConfigurationFactory;
    }

    public function getClient(string $salesChannelId): ClientInterface
    {
        if (!isset($this->clientPerSalesChannelHashMap[$salesChannelId])) {
            $clientConfiguration = $this->clientConfigurationFactory->create($salesChannelId);
            $this->clientPerSalesChannelHashMap[$salesChannelId] = $this->clientFactory->create($clientConfiguration);
        }

        return $this->clientPerSalesChannelHashMap[$salesChannelId];
    }
}
