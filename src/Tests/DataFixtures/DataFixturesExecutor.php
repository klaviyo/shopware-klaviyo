<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;

class DataFixturesExecutor
{
    private array $listOfAlreadyLoadedFixtures = [];
    private array $listOfFixturesWhichExecutionInProgress = [];
    private ReferencesRegistry $referencesRegistry;

    public function __construct(ReferencesRegistry $referencesRegistry)
    {
        $this->referencesRegistry = $referencesRegistry;
    }

    /**
     * @param ContainerInterface $container
     * @param TestDataFixturesInterface[] $fixtures
     */
    public function executeDataFixtures(ContainerInterface $container, array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->executeDataFixture($container, $fixture);
        }
    }

    public function executeDataFixture(ContainerInterface $container, TestDataFixturesInterface $fixture)
    {
        try {
            if (isset($this->listOfAlreadyLoadedFixtures[get_class($fixture)])) {
                // Fixture is loaded already
                return;
            }
            $this->listOfFixturesWhichExecutionInProgress[get_class($fixture)] = true;

            if ($fixture instanceof DependentTestDataFixtureInterface) {
                $dependenciesList = $fixture->getDependenciesList();
                foreach ($dependenciesList as $dependency) {
                    if (isset($this->listOfFixturesWhichExecutionInProgress[get_class($dependency)])) {
                        throw new \LogicException(
                            sprintf(
                                'Circular reference detected while loading a fixture[class: %s]',
                                get_class($dependency)
                            ),
                        );
                    }

                    $this->executeDataFixture($container, $dependency);
                }
            }

            $this->doExecuteSimpleFixture($container, $fixture);
        } finally {
            $this->listOfFixturesWhichExecutionInProgress = [];
        }
    }

    private function doExecuteSimpleFixture(ContainerInterface $container, TestDataFixturesInterface $fixture)
    {
        $fixture->execute($container, $this->referencesRegistry);
        $this->listOfAlreadyLoadedFixtures[get_class($fixture)] = true;
        unset($this->listOfFixturesWhichExecutionInProgress[get_class($fixture)]);
    }
}
