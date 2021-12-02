<?php

namespace Klaviyo\Integration\Klaviyo\CatalogFeed;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class CatalogFeedConstructor
{
    private EntityRepositoryInterface $entityRepository;
    private CatalogFeedProductItemTranslator $catalogFeedProductItemTranslator;
    private ConfigurationRegistry $configurationRegistry;

    public function __construct(
        EntityRepositoryInterface $entityRepository,
        CatalogFeedProductItemTranslator $catalogFeedProductItemTranslator,
        ConfigurationRegistry $configurationRegistry
    ) {
        $this->entityRepository = $entityRepository;
        $this->catalogFeedProductItemTranslator = $catalogFeedProductItemTranslator;
        $this->configurationRegistry = $configurationRegistry;
    }

    public function constructCatalogFeed(Context $context, SalesChannelEntity $salesChannelEntity): CatalogFeedProductItemCollection
    {
        $configuration = $this->configurationRegistry->getConfiguration($salesChannelEntity);

        $catalogFeedProductsCount = $configuration->getCatalogFeedProductsCount();
        $products = $this->getMostRecentCatalogFeedProducts($context, $catalogFeedProductsCount);

        return $this->catalogFeedProductItemTranslator->translateProducts($context, $products);
    }

    private function getMostRecentCatalogFeedProducts(Context $context, int $numberOfProducts): ProductCollection
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt', 'DESC'));
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->setLimit($numberOfProducts);
        /** @var ProductCollection $result */
        $result = $this->entityRepository->search($criteria, $context)->getEntities();

        return $result;
    }
}