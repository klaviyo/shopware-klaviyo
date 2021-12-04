<?php declare(strict_types=1);

namespace Klaviyo\Integration\Tests\LoadTests;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\VirtualProxyJobScheduler;
use Klaviyo\Integration\Test\IntRange;
use Klaviyo\Integration\Test\KlaviyoSubscriberManagement;
use Klaviyo\Integration\Tests\AbstractIntegrationTestCase;
use Klaviyo\Integration\Tests\DataFixtures;
use Klaviyo\Integration\Tracking\Job\HistoricalEventsTrackingJobProcessor;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class HistoricalDataSync1KTest extends AbstractIntegrationTestCase
{
    use BasicTestDataBehaviour;

    const BATCH_SIZE = 500;

    private KlaviyoSubscriberManagement $subscriberManagement;
    private HistoricalEventsTrackingJobProcessor $historyDataSyncProcessor;
    private VirtualProxyJobScheduler $jobScheduler;
    private EntityRepositoryInterface $customerRepo;
    private EntityRepositoryInterface $orderRepo;
    private string $currentListId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executeFixtures([new DataFixtures\RegisterDefaultSalesChannel()]);

        /** @var SalesChannelEntity $salesChannelEntity */
        $salesChannelEntity = $this->getByReference('klaviyo_tracking_integration.sales_channel.storefront');
        $klaviyoGateway = $this->getContainer()->get('klaviyo.tracking_integration.gateway.test.public');
        $this->historyDataSyncProcessor = $this->getContainer()->get('klaviyo.tracking_integration.tracking.job.historical_events_job_processor.test.public');
        $this->jobScheduler = $this->getContainer()->get(VirtualProxyJobScheduler::class);
        $this->subscriberManagement = new KlaviyoSubscriberManagement($salesChannelEntity, $klaviyoGateway);
        $this->customerRepo = $this->getContainer()->get('customer.repository');
        $this->orderRepo = $this->getContainer()->get('order.repository');

        $countryId = $this->getValidCountryId($salesChannelEntity->getId());
        $salutationId = $this->getValidSalutationId();
        $orderNumberRange = new IntRange(1, 3);
        $productNumberRange = new IntRange(1, 3);

        $this->executeFixtures([new DataFixtures\HistoricalData\GenerateOrdersAndCustomers(
            $countryId,
            $salutationId,
            1000,
            $orderNumberRange,
            $productNumberRange
        )]);

        $this->subscriberManagement->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
        $this->currentListId = $this->subscriberManagement->createKlaviyoTestList(KLAVIYO_LIST_NAME);
    }

    public function testSyncBigAmountOfHistoricalData()
    {
        $defaultContext = Context::createDefaultContext();
        $this->jobScheduler->scheduleJob($defaultContext, JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE);
        /** @var JobEntity $newJob */
        $newJob = $this->getJobs()->last();

        self::assertEquals(JobEntity::STATUS_PENDING, $newJob->getStatus());

        $this->historyDataSyncProcessor->process($defaultContext, $newJob);

        $customerCriteria = new Criteria();
        $customerCriteria->setLimit(self::BATCH_SIZE);
        $customerCriteria->addFilter(new PrefixFilter('customerNumber', 'phpunit_history_'));
        $iterator = new RepositoryIterator($this->customerRepo, $defaultContext, $customerCriteria);
        $emails = [];

        while (($result = $iterator->fetch()) !== null) {
            /** @var CustomerEntity $customer */
            foreach ($result->getEntities() as $customer) {
                $emails[] = $customer->getEmail();
            }
        }

        $personsData = $this->subscriberManagement->addEmailsToList($this->currentListId, $emails);
        $personsData = array_combine(array_column($personsData, 'email'), array_column($personsData, 'id'));
        $personsDataBatches = array_chunk($personsData, self::BATCH_SIZE, true);

        foreach ($personsDataBatches as $personsBatch) {
            $orderCriteria = new Criteria();
            $orderCriteria->addAssociation('orderCustomer.customer');
            $orderCriteria->addAssociation('lineItems.product.manufacturer');
            $orderCriteria->addFilter(
                new EqualsAnyFilter('order.orderCustomer.email', array_keys($personsBatch))
            );
            /** @var OrderEntity[] $orders */
            $orders = $this->orderRepo->search($orderCriteria, $defaultContext)->getElements();

            foreach ($personsBatch as $email => $personId) {
                $customerOrders = $this->findOrdersByCustomerEmail($orders, $email);
                $profileMetrics = $this->subscriberManagement->getProfileMetrics($personId);

                self::assertNotEmpty(isset($profileMetrics['data']), 'No events tracked for person.');
                self::assertPlacedOrderTracking($customerOrders, $profileMetrics);
                self::assertOrderedProductTracking($customerOrders, $profileMetrics);
            }
        }

        /** @var JobEntity $newJob */
        $newJob = $this->getJobs()->last();
        self::assertEquals(JobEntity::STATUS_SUCCESS, $newJob->getStatus());
    }

    /**
     * FYI Klaviyo tracks only unique products for "Ordered Product" event.
     *
     * @param OrderEntity[] $customerOrders
     * @param array $profileMetrics
     */
    private static function assertOrderedProductTracking(array $customerOrders, array $profileMetrics): void
    {
        $orderedProductEvents = array_filter($profileMetrics['data'], function ($eventData) {
            return $eventData['event_name'] === 'Ordered Product';
        });
        $uniqueOrdersLineItems = [];

        foreach ($customerOrders as $order) {
            foreach ($order->getLineItems() as $lineItem) {
                if (!array_key_exists($lineItem->getProductId(), $uniqueOrdersLineItems)) {
                    $uniqueOrdersLineItems[$lineItem->getProductId()] = $lineItem;
                }
            }
        }

        self::assertEquals(count($uniqueOrdersLineItems), count($orderedProductEvents), 'Not all ordered product events was tracked for person.');

        /** @var OrderLineItemEntity $lineItem */
        foreach ($uniqueOrdersLineItems as $productId => $lineItem) {
            $orderedProductEvent = array_filter($orderedProductEvents, function ($event) use ($lineItem) {
                return $lineItem->getProductId() === $event['event_properties']['ProductID'];
            });
            $orderedProductEvent = current($orderedProductEvent);

            self::assertNotEmpty($orderedProductEvent, 'Specific ordered product was not tracked');
            $orderEventProps = $orderedProductEvent['event_properties'];

            self::assertEquals($lineItem->getLabel(), $orderEventProps['ProductName']);
            self::assertEquals($lineItem->getIdentifier(), $orderEventProps['SKU']);
            self::assertEquals($lineItem->getQuantity(), $orderEventProps['Quantity']);
            self::assertEquals($lineItem->getUnitPrice(), $orderEventProps['$value']);
            self::assertNotNull($lineItem->getProduct()->getManufacturer());
            self::assertEquals($lineItem->getProduct()->getManufacturer()->getName(), $orderEventProps['ProductBrand']);
        }
    }

    /**
     * @param OrderEntity[] $customerOrders
     * @param array $profileMetrics
     */
    private static function assertPlacedOrderTracking(array $customerOrders, array $profileMetrics): void
    {
        $placedOrderEvents = array_filter($profileMetrics['data'], function ($eventData) {
            return $eventData['event_name'] === 'Placed Order';
        });

        self::assertEquals(count($customerOrders), count($placedOrderEvents), 'Not all order placed events was tracked for person.');

        foreach ($customerOrders as $order) {
            $orderEvent = array_filter($placedOrderEvents, function ($event) use ($order) {
                return $order->getId() === $event['event_properties']['OrderId'];
            });
            $orderEvent = current($orderEvent);

            self::assertNotEmpty($orderEvent, 'Specific order was not tracked');
            $orderEventProps = $orderEvent['event_properties'];
            self::assertEquals($order->getAmountTotal(), $orderEventProps['$value'], 'Order totals do not match.');
            self::assertPlacedOrderItemsTracking($order->getLineItems(), $orderEventProps['Items']);
        }
    }

    private static function assertPlacedOrderItemsTracking(OrderLineItemCollection $lineItems, array $orderEventItems): void
    {
        self::assertEquals(count($lineItems), count($orderEventItems), 'Order line items count do not match.');

        foreach ($lineItems as $lineItem) {
            $eventItem = array_filter($orderEventItems, function ($eventItem) use ($lineItem) {
                return $eventItem['ProductID'] === $lineItem->getId();
            });
            $eventItem = current($eventItem);

            self::assertNotEmpty($eventItem, 'Specific order line item was not tracked.');
            self::assertEquals($lineItem->getIdentifier(), $eventItem['SKU']);
            self::assertEquals($lineItem->getLabel(), $eventItem['ProductName']);
            self::assertEquals($lineItem->getQuantity(), $eventItem['Quantity']);
            self::assertEquals($lineItem->getUnitPrice(), $eventItem['ItemPrice']);
            self::assertEquals($lineItem->getTotalPrice(), $eventItem['RowTotal']);
            self::assertNotNull($lineItem->getProduct()->getManufacturer());
            self::assertEquals($lineItem->getProduct()->getManufacturer()->getName(), $eventItem['Brand']);
        }
    }

    /**
     * @param array $orders
     * @param string $email
     * @return OrderEntity[]
     */
    private function findOrdersByCustomerEmail(array $orders, string $email): array
    {
        return array_filter($orders, function (OrderEntity $order) use ($email) {
            return $order->getOrderCustomer()->getEmail() === $email;
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->subscriberManagement->deleteKlaviyoTestList(KLAVIYO_LIST_NAME);
    }
}
