<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Doctrine\DBAL\Exception;
use Klaviyo\Integration\Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Klaviyo\Integration\Async\Message\AbstractDateBasedMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FullCustomerOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-customer-order-sync-handler';
    public const SYNC_CUSTOMER_OFFSET_CONFIG_KEY = 'klavi_overd.cron.fullCustomerOrderSyncOffset';
    public const IS_ENABLED_WITHOUT_ORDERS_SYNC = 'klavi_overd.config.withoutOrdersSync';
    private const CUSTOMER_BATCH_SIZE = 100;

    public function __construct(
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly EntityRepository      $customerRepository,
        private readonly GetValidChannels      $getValidChannels,
        private readonly LoggerInterface       $logger,
        private readonly SystemConfigService   $systemConfigService,
        private readonly ConfigService   $configService
    ) {
    }

    /**
     * @throws Exception
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $channelIds = $this->getValidChannels->execute($message->getContext())->map(
            fn(SalesChannelEntity $channel) => $channel->getId()
        );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $offset = $this->configService->getConfigValueWithoutCache(self::SYNC_CUSTOMER_OFFSET_CONFIG_KEY);

        do {
            try {
                $criteria = new Criteria();
                $criteria->setLimit(self::CUSTOMER_BATCH_SIZE);
                $criteria->setOffset($offset);
                $criteria->addFilter(
                    new EqualsFilter('orderCount', 0),
                    new EqualsAnyFilter('salesChannelId', $channelIds)
                );

                if ($message instanceof AbstractDateBasedMessage) {
                    $message->applyDateRangeFilter($criteria);
                }

                $this->logger->notice("Customers offset : $offset");

                $customers = $this->customerRepository->search($criteria, $message->getContext());
                $customerIds = $customers->getIds();

                if (!empty($customerIds)) {
                    $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob(
                        $customerIds,
                        $message->getJobId(),
                        $message->getContext()
                    );
                    $result->addMessage(new Message\InfoMessage(\sprintf('Scheduled job for %d customers. Offset: %d', count($customerIds), $offset)));
                    $offset = (int)$offset + self::CUSTOMER_BATCH_SIZE;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['data' => json_encode($e)]);
                $result->addMessage(new Message\WarningMessage($e->getMessage()));
                return $result;
            }
        } while (!empty($customerIds));

        $this->logger->notice("All customers have been processed.");
        $this->systemConfigService->set(self::SYNC_CUSTOMER_OFFSET_CONFIG_KEY, -1);
        $result->addMessage(new Message\InfoMessage('All customers have been processed.'));

        return $result;
    }
}
