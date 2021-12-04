<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\DataFixtures\HistoricalData;

use Klaviyo\Integration\Test\IntRange;
use Klaviyo\Integration\Tests\DataFixtures;
use Klaviyo\Integration\Tests\DataFixtures\ReferencesRegistry;
use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Order\Transformer\AddressTransformer;
use Shopware\Core\Checkout\Cart\Order\Transformer\CustomerTransformer;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\Test\TestDataCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\StateMachineRegistry;

class GenerateOrdersAndCustomers implements DataFixtures\TestDataFixturesInterface,
    DataFixtures\DependentTestDataFixtureInterface
{
    private const BATCH_SIZE = 500;
    private string $countryId;
    private string $salutationId;
    private int $customerNumber;
    private IntRange $orderNumberRange;
    private IntRange $productNumberRange;
    private TestDataCollection $ids;
    private string $deepLinkCode;
    private string $validShippingMethodId;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;
    private $defaultPaymentMethodId;

    public function __construct(
        string $countryId,
        string $salutationId,
        int $customerNumber = 100,
        ?IntRange $orderNumberRange = null,
        ?IntRange $productNumberRange = null
    ) {
        $this->countryId = $countryId;
        $this->salutationId = $salutationId;
        $this->customerNumber = $customerNumber;
        $this->orderNumberRange = $orderNumberRange ?? new IntRange(1, 2);
        $this->productNumberRange = $productNumberRange ?? new IntRange(1, 2);
        $this->ids = new TestDataCollection(Context::createDefaultContext());
    }

    public function getDependenciesList(): array
    {
        return [
            new GenerateCustomers($this->countryId, $this->salutationId, $this->customerNumber),
            new GenerateProducts($this->ids, $this->productNumberRange)
        ];
    }

    public function execute(ContainerInterface $container, ReferencesRegistry $referencesRegistry)
    {
        $this->stateMachineRegistry = $container->get(StateMachineRegistry::class);
        $this->defaultPaymentMethodId = $this->getValidPaymentMethods($container)->first()->getId();
        $this->validShippingMethodId = $this->getValidShippingMethodId($container);
        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $container->get('customer.repository');
        /** @var EntityRepositoryInterface $orderRepo */
        $orderRepo = $container->get('order.repository');
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(self::BATCH_SIZE);
        $criteria->addFilter(new PrefixFilter('customerNumber', 'phpunit_history_'));
        $criteria->addAssociation('addresses');

        $iterator = new RepositoryIterator($customerRepo, $context, $criteria);

        while (($result = $iterator->fetch()) !== null) {
            $customers = $result->getEntities();
            $orders = [];

            /** @var CustomerEntity $customer */
            foreach ($customers as $customer) {
                $ordersToGenerateForCustomer = $this->orderNumberRange->getRandom();

                for ($i = 0; $i < $ordersToGenerateForCustomer; $i++) {
                    $orders[] = $this->getOrderDataForCustomer($customer, $context);
                }

                if (count($orders) === self::BATCH_SIZE) {
                    $orderRepo->create($orders, $context);
                    $orders = [];
                }
            }
        }

        if (!empty($orders)) {
            $orderRepo->create($orders, $context);
        }
    }

    private function getOrderDataForCustomer(CustomerEntity $customer, Context $context): array
    {
        $orderPrice = \Shopware\Core\Framework\Util\Random::getInteger(5, 500);
        $orderAddress = AddressTransformer::transform($customer->getAddresses()->first());
        $orderAddress['id'] = $customer->getAddresses()->first()->getId();

        $orderData = [
            'id' => Uuid::randomHex(),
            'orderNumber' => Uuid::randomHex(),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice($orderPrice, $orderPrice, $orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice($orderPrice, $orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $this->stateMachineRegistry->getInitialState(OrderStates::STATE_MACHINE, $context)->getId(),
            'paymentMethodId' => $this->defaultPaymentMethodId,
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'transactions' => [
                [
                    'id' => Uuid::randomHex(),
                    'paymentMethodId' => $this->defaultPaymentMethodId,
                    'amount' => [
                        'unitPrice' => 5.0,
                        'totalPrice' => $orderPrice + 5.0,
                        'quantity' => 3,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                    ],
                    'stateId' => $this->stateMachineRegistry->getInitialState(
                        OrderTransactionStates::STATE_MACHINE,
                        $context
                    )->getId(),
                ],
            ],
            'deliveries' => [
                [
                    'stateId' => $this->stateMachineRegistry->getInitialState(OrderDeliveryStates::STATE_MACHINE, $context)->getId(),
                    'shippingMethodId' => $this->validShippingMethodId,
                    'shippingCosts' => new CalculatedPrice($orderPrice, $orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'shippingDateEarliest' => date(\DATE_ISO8601),
                    'shippingDateLatest' => date(\DATE_ISO8601),
                    'shippingOrderAddress' => AddressTransformer::transform($customer->getAddresses()->first()),
                ],
            ],
            'deepLinkCode' => $this->deepLinkCode = Uuid::randomHex(),
            'orderCustomer' => CustomerTransformer::transform($customer),
            'billingAddressId' => $customer->getAddresses()->first()->getId(),
            'addresses' => [$orderAddress],
        ];

        foreach ($this->productNumberRange->getRandomUniqueValuesFromRange() as $randomProductNumber) {
            $orderLineItemId = Uuid::randomHex();
            $productId = $this->ids->get('product_id_' . $randomProductNumber);
            $productName = $this->ids->get('product_name_' . $randomProductNumber);
            $productPrice = (float)$this->ids->get('product_price_' . $randomProductNumber);
            $price = new CalculatedPrice($productPrice, $productPrice, new CalculatedTaxCollection(), new TaxRuleCollection());
            $orderData['lineItems'][] = [
                'id' => $orderLineItemId,
                'versionId' => $context->getVersionId(),
                'identifier' => $productId,
                'quantity' => 1,
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'label' => $productName,
                'price' => $price,
                'priceDefinition' => new QuantityPriceDefinition(10, new TaxRuleCollection(), 2),
                'priority' => 100,
                'good' => true,
                'referencedId' => $productId,
                'productId' => $productId,
            ];
        }

        return $orderData;
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

    protected function getValidShippingMethodId(ContainerInterface $container): string
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $container->get('shipping_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        return $repository->searchIds($criteria, Context::createDefaultContext())->getIds()[0];
    }
}
