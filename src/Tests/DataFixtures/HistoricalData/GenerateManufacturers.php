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

class GenerateManufacturers implements DataFixtures\TestDataFixturesInterface
{
    private TestDataCollection $ids;
    private IntRange $manufacturerNumberRange;

    public function __construct(
        TestDataCollection $ids,
        ?IntRange $manufacturerNumberRange = null
    ) {
        $this->ids = $ids;
        $this->manufacturerNumberRange = $manufacturerNumberRange ?? new IntRange(1, 1);
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var EntityRepositoryInterface $manufacturerRepository */
        $manufacturerRepository = $container->get('product_manufacturer.repository');
        $manufacturerNumberToGenerate = $this->manufacturerNumberRange->getMax();
        $manufacturers = [];

        for ($i = $this->manufacturerNumberRange->getMin(); $i <= $manufacturerNumberToGenerate; $i++) {
            $id = Uuid::randomHex();
            $manufacturers[] = [
                'id' => $id,
                'name' => 'PHPUnit Test Manufacturer ' . $i
            ];
            $this->ids->set('manufacturer_' . $i, $id);
        }

        $manufacturerRepository->create($manufacturers, Context::createDefaultContext());
    }
}
