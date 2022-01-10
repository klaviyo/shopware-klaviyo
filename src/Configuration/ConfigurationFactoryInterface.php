<?php

namespace Klaviyo\Integration\Configuration;

interface ConfigurationFactoryInterface
{
    public function create(?string $salesChannelId = null): ConfigurationInterface;
}
