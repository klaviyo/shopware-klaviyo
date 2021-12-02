<?php

namespace Klaviyo\Integration\Klaviyo\Gateway;

use Klaviyo\Integration\Klaviyo\Client\Client;
use Klaviyo\Integration\Klaviyo\Client\ClientFactory;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class ClientRegistry
{
    /**
     * @var array|Client
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

    public function getClient(SalesChannelEntity $salesChannelEntity): Client
    {
        $salesChannelId = $salesChannelEntity->getId();
        if (!isset($this->clientPerSalesChannelHashMap[$salesChannelId])) {
            $clientConfiguration = $this->clientConfigurationFactory->create($salesChannelEntity);
            $this->clientPerSalesChannelHashMap[$salesChannelId] = $this->clientFactory->create($clientConfiguration);
        }

        return $this->clientPerSalesChannelHashMap[$salesChannelId];
    }
}