<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;

interface TestDataFixturesInterface
{
    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry);
}
