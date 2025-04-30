<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Doctrine\DBAL\Exception;
use Klaviyo\Integration\Async\Message\AbstractDateBasedMessage;
use Klaviyo\Integration\Async\Message\FullOrderSyncMessage;
use Klaviyo\Integration\Async\Message\OrderSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FullOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-order-sync-handler';
    public const SYNC_ORDER_OFFSET_CONFIG_KEY = 'klavi_overd.cron.fullOrderSyncOffset';
    private const ORDER_BATCH_SIZE = 100;

    public function __construct(
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly EntityRepository      $orderRepository,
        private readonly GetValidChannels      $getValidChannels,
        private readonly LoggerInterface       $logger,
        private readonly SystemConfigService   $systemConfigService,
        private readonly ConfigService   $configService
    ){
    }

    /**
     * @param OrderSyncMessage|FullOrderSyncMessage $message
     * @return JobResult
     * @throws Exception
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();

        $channelIds = $this->getValidChannels->execute($message->getContext())
            ->map(fn(SalesChannelEntity $channel) => $channel->getId());

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));
            return $result;
        }

        $offset = $this->configService->getConfigValueWithoutCache(self::SYNC_ORDER_OFFSET_CONFIG_KEY);

        do {
            try {
                $criteria = new Search\Criteria();
                $criteria->addFilter(new Search\Filter\EqualsAnyFilter('salesChannelId', \array_values($channelIds)));
                $criteria->setLimit(self::ORDER_BATCH_SIZE);
                $criteria->setOffset($offset);

                if ($message instanceof AbstractDateBasedMessage) {
                    $message->applyDateRangeFilter($criteria, 'orderDateTime');
                }

                $this->logger->notice("Offset: $offset");

                $orders = $this->orderRepository->search($criteria, $message->getContext());
                $orderIds = $orders->getIds();

                if (!empty($orderIds)) {
                    $this->scheduleBackgroundJob->scheduleOrderSync($orderIds, $message->getJobId(), $message->getContext());
                    $result->addMessage(new Message\InfoMessage(\sprintf('Scheduled job for %d orders. Offset: %d', count($orderIds), $offset)));
                    $offset = (int)$offset + self::ORDER_BATCH_SIZE;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['data' => json_encode($e)]);
                $result->addMessage(new Message\WarningMessage($e->getMessage()));
                return $result;
            }
        } while (!empty($orderIds));

        $this->logger->notice("All orders have been processed.");
        $this->systemConfigService->set(self::SYNC_ORDER_OFFSET_CONFIG_KEY, -1);
        $result->addMessage(new Message\InfoMessage('All orders have been processed.'));

        return $result;
    }
}
