<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

abstract class AbstractTestDataFixture implements DependentTestDataFixtureInterface
{
    public function getDependenciesList(): array
    {
        return [];
    }

    protected function resolveReferencesAsIdsIfExists(
        ReferencesRegistry $referencesRegistry,
        array &$record,
        array $columnsToResolve
    ) {
        foreach ($columnsToResolve as $columnName) {
            if (!empty($record[$columnName])) {
                if (is_array($record[$columnName])) {
                    $transformedData = [];
                    foreach ($record[$columnName] as $reference) {
                        $transformedData[] = ['id' => $this->getEntityIdByReference($referencesRegistry, $reference)];
                    }

                    $record[$columnName] = $transformedData;
                } else {

                    $record[$columnName] = $this->getEntityIdByReference($referencesRegistry, $record[$columnName]);
                }
            }
        }
    }

    protected function getEntityIdByReference(ReferencesRegistry $referencesRegistry, string $reference): string
    {
        /** @var Entity $entity */
        $entity = $referencesRegistry->getByReference($reference);

        return $entity->getUniqueIdentifier();
    }

    protected function createEntity(EntityRepositoryInterface $repository, array $record): Entity
    {
        $event = $repository->create([$record], Context::createDefaultContext());
        $keys = $event->getPrimaryKeys($repository->getDefinition()->getEntityName());

        return $repository
            ->search(new Criteria([$keys]), Context::createDefaultContext())
            ->getEntities()
            ->first();
    }

    protected function updateSeoUrl(
        ContainerInterface $container,
        string $foreignKeyId,
        SalesChannelEntity $salesChannelEntity,
        string $url
    ) {
        /** @var EntityRepositoryInterface $repository */
        $repository = $container->get('seo_url.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelEntity->getId()));
        $criteria->addFilter(new EqualsFilter('foreignKey', $foreignKeyId));

        $result = $repository->searchIds($criteria, Context::createDefaultContext())->getIds();

        $repository->update(
            [['id' => $result[0], 'seoPathInfo' => $url, 'isCanonical' => true]],
            Context::createDefaultContext()
        );
    }
}
