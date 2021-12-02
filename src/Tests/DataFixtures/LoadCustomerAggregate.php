<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;

class LoadCustomerAggregate implements TestDataFixturesInterface, DependentTestDataFixtureInterface
{

    public function getDependenciesList(): array
    {
        return [
            new LoadCustomerData(),
            new LoadCustomerAddressesData()
        ];
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
    }
}