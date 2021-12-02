<?php

namespace Klaviyo\Integration\Configuration;

use Shopware\Core\System\SalesChannel\SalesChannelEntity;

interface ConfigurationFactoryInterface
{
    public function create(?SalesChannelEntity $salesChannelEntity): ConfigurationInterface;
}