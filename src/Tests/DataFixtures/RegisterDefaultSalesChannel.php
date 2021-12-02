<?php

namespace Klaviyo\Integration\Tests\DataFixtures;

use Psr\Container\ContainerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class RegisterDefaultSalesChannel implements TestDataFixturesInterface
{
    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $container->get('sales_channel.repository');

        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));

        $salesChannel = $salesChannelRepository->search($criteria, Context::createDefaultContext())
            ->getEntities()
            ->first();

        $referencesRegistry->setReference('klaviyo_tracking_integration.sales_channel.storefront', $salesChannel);
    }
}
