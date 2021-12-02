<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;

class LoadOrderData extends AbstractTestDataFixture
{
    protected array $data = [

    ];


    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {

    }

    public function getDependenciesList(): array
    {
        return [
            new LoadCustomerAggregate(),
            new LoadProductData()
        ];
    }
}