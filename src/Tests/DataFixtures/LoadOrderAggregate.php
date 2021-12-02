<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;

class LoadOrderAggregate implements TestDataFixturesInterface, DependentTestDataFixtureInterface
{
    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
    }

    public function getDependenciesList(): array
    {
        return [
            new LoadOrderAddressData(),
            new LoadOrderData()
        ];
    }
}