<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\DataFixtures\HistoricalData;

use Klaviyo\Integration\Test\IntRange;
use Klaviyo\Integration\Tests\DataFixtures;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GenerateProducts implements DataFixtures\TestDataFixturesInterface,
    DataFixtures\DependentTestDataFixtureInterface
{
    private const INSERT_BATCH_SIZE = 500;

    private TestDataCollection $ids;
    private IntRange $productNumberRange;
    private IntRange $manufacturersNumberRange;
    private IntRange $taxNumberRange;

    public function __construct(
        TestDataCollection $ids,
        IntRange $productNumberRange,
        ?IntRange $manufacturersNumberRange = null,
        ?IntRange $taxNumberRange = null
    ) {
        $this->ids = $ids;
        $this->productNumberRange = $productNumberRange;
        $subEntitiesCount = $productNumberRange->getMax() < 10 ? $productNumberRange->getMax() : 10;
        $this->manufacturersNumberRange = $manufacturersNumberRange ?? new IntRange(1, $subEntitiesCount);
        $this->taxNumberRange = $taxNumberRange ?? new IntRange(1, $subEntitiesCount);
    }

    public function getDependenciesList(): array
    {
        return [
            new DataFixtures\RegisterDefaultSalesChannel(),
            new GenerateManufacturers($this->ids, $this->manufacturersNumberRange),
            new GenerateTaxes($this->ids, $this->taxNumberRange)
        ];
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $referencesRegistry->getByReference('klaviyo_tracking_integration.sales_channel.storefront');
        /** @var EntityRepositoryInterface $productRepository */
        $productRepository = $container->get('product.repository');
        $context = Context::createDefaultContext();
        $products = [];

        for ($i = $this->productNumberRange->getMin(); $i <= $this->productNumberRange->getMax(); $i++) {
            $productPrice = \Shopware\Core\Framework\Util\Random::getInteger(20, 500);
            $productId = Uuid::randomHex();
            $productName = 'Generated Test Product ' . $i;
            $this->ids->set('product_id_' . $i, $productId);
            $this->ids->set('product_name_' . $i, $productName);
            $this->ids->set('product_price_' . $i, (string)$productPrice);
            $products[] = [
                'id' => $productId,
                'productNumber' => $productId,
                'stock' => 10,
                'name' => $productName,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => $productPrice, 'net' => $productPrice, 'linked' => false]],
                'manufacturerId' => $this->ids->get('manufacturer_' . $this->manufacturersNumberRange->getRandom()),
                'taxId' => $this->ids->get('tax_' . $this->taxNumberRange->getRandom()),
                'active' => true,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelEntity->getId(),
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL
                    ],
                ],
            ];
        }

        foreach (array_chunk($products, self::INSERT_BATCH_SIZE) as $productsBatch) {
            $productRepository->create($productsBatch, $context);
        }
    }

    protected function getValidTaxId(ContainerInterface $container): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $container->get('shipping_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        return $repository->searchIds($criteria, Context::createDefaultContext())->getIds()[0];
    }
}
