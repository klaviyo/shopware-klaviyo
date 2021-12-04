<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\DataFixtures\HistoricalData;

use Klaviyo\Integration\Test\IntRange;
use Klaviyo\Integration\Tests\DataFixtures;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Framework\Uuid\Uuid;

class GenerateTaxes implements DataFixtures\TestDataFixturesInterface
{
    private TestDataCollection $ids;
    private IntRange $taxNumberRange;

    public function __construct(
        TestDataCollection $ids,
        ?IntRange $taxNumberRange = null
    ) {
        $this->ids = $ids;
        $this->taxNumberRange = $taxNumberRange ?? new IntRange(1, 1);
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var EntityRepositoryInterface $taxRepository */
        $taxRepository = $container->get('tax.repository');
        $taxNumberToGenerate = $this->taxNumberRange->getMax();
        $taxes = [];

        for ($i = $this->taxNumberRange->getMin(); $i <= $taxNumberToGenerate; $i++) {
            $id = Uuid::randomHex();
            $taxes[] = ['id' => $id, 'name' => 'Tax rate #' . $i, 'taxRate' => 10 * $i];
            $this->ids->set('tax_' . $i, $id);
        }

        $taxRepository->create($taxes, Context::createDefaultContext());
    }
}
