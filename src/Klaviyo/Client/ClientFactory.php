<?php

namespace Klaviyo\Integration\Klaviyo\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Translator\TranslatorsRegistryFactory;
use Klaviyo\Integration\Klaviyo\Client\Configuration\ConfigurationInterface;

class ClientFactory
{
    private TranslatorsRegistryFactory $translatorsRegistryFactory;
    private GuzzleClient $guzzleClient;

    public function __construct(TranslatorsRegistryFactory $translatorsRegistryFactory, GuzzleClient $guzzleClient)
    {
        $this->translatorsRegistryFactory = $translatorsRegistryFactory;
        $this->guzzleClient = $guzzleClient;
    }

    public function create(ConfigurationInterface $configuration): ClientInterface
    {
        $useAsync = true;
        $translatorRegistry = $this->translatorsRegistryFactory->create($configuration);

        if ($useAsync) {
            return new AsyncClient($translatorRegistry, $this->guzzleClient, $configuration);
        }

        return new Client($translatorRegistry, $this->guzzleClient, $configuration);
    }
}
