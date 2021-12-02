<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

interface DependentTestDataFixtureInterface extends TestDataFixturesInterface
{
    public function getDependenciesList(): array;
}
