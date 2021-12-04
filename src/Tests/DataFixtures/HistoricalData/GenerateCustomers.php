<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\DataFixtures\HistoricalData;

use Klaviyo\Integration\Tests\DataFixtures;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use Psr\Container\ContainerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class GenerateCustomers implements DataFixtures\TestDataFixturesInterface,
    DataFixtures\DependentTestDataFixtureInterface
{
    const INSERT_BATCH = 500;
    private string $countryId;
    private string $salutationId;
    private int $recordsNumber;

    public function __construct(
        string $countryId,
        string $salutationId,
        int $recordsNumber = 100
    ) {
        $this->countryId = $countryId;
        $this->salutationId = $salutationId;
        $this->recordsNumber = $recordsNumber;
    }

    public function getDependenciesList(): array
    {
        return [
            new DataFixtures\RegisterDefaultSalesChannel()
        ];
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $referencesRegistry->getByReference('klaviyo_tracking_integration.sales_channel.storefront');
        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $container->get('customer.repository');
        $faker = new \Faker\Generator();
        $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
        $faker->addProvider(new \Faker\Provider\en_US\Address($faker));
        $context = Context::createDefaultContext();
        $defaultPaymentMethodId = $this->getValidPaymentMethods($container)->first()->getId();
        $customers = [];

        for ($i = 0; $i < $this->recordsNumber; $i++) {
            $customerId = Uuid::randomHex();
            $addressId = Uuid::randomHex();
            $customerFirstName = $faker->firstName;
            $customerLastName = $faker->lastName;
            $customers[] = [
                'id' => $customerId,
                'salesChannelId' => $salesChannelEntity->getId(),
                'defaultShippingAddress' => [
                    'id' => $addressId,
                    'firstName' => $customerFirstName,
                    'lastName' => $customerLastName,
                    'street' => $faker->streetName,
                    'city' => $faker->city,
                    'zipcode' => $faker->postcode,
                    'salutationId' => $this->salutationId,
                    'countryId' => $this->countryId,
                ],
                'defaultBillingAddressId' => $addressId,
                'defaultPaymentMethodId' => $defaultPaymentMethodId,
                'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
                'email' => 'phpunit_history_' . Uuid::randomHex() . '@gmail.com',
                'password' => Uuid::randomHex(),
                'firstName' => $customerFirstName,
                'lastName' => $customerLastName,
                'salutationId' => $this->salutationId,
                'customerNumber' => 'phpunit_history_' . $i,
            ];

            if (count($customers) === self::INSERT_BATCH) {
                $customerRepo->create($customers, $context);
                $customers = [];
            }
        }

        if (!empty($customers)) {
            $customerRepo->create($customers, $context);
        }
    }

    protected function getValidPaymentMethods(ContainerInterface $container): EntitySearchResult
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $container->get('payment_method.repository');

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('availabilityRuleId', null))
            ->addFilter(new EqualsFilter('active', true));

        return $repository->search($criteria, Context::createDefaultContext());
    }
}
