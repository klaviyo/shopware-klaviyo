<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\OrderSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FullOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-order-sync-handler';
    private const ORDER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepository $orderRepository;
    private GetValidChannels $getValidChannels;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepository      $orderRepository,
        GetValidChannels      $getValidChannels,
        LoggerInterface       $logger,
        SystemConfigService   $systemConfigService
    )
    {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
        $this->getValidChannels = $getValidChannels;
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param OrderSyncMessage $message
     * @return JobResult
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

        $offset = $this->systemConfigService->get('klavi_overd.cron.fullOrderSyncOffset');

        $this->logger->notice("Offset: $offset");

        for ($i = 0; $i < 5; $i++) {
            try {
                $criteria = new Search\Criteria();
                $criteria->addFilter(new Search\Filter\EqualsAnyFilter('salesChannelId', \array_values($channelIds)));
                $criteria->setLimit(self::ORDER_BATCH_SIZE);
                $criteria->setOffset($offset);

                $orders = $this->orderRepository->search($criteria, $message->getContext());
                $orderIds = $orders->getIds();
                if (!empty($orderIds)) {
                    $this->scheduleBackgroundJob->scheduleOrderSync($orderIds, $message->getJobId(), $message->getContext());
                    $result->addMessage(new Message\InfoMessage(\sprintf('Scheduled job for %d orders. Offset: %d', count($orderIds), $offset)));
                    $offset = (int)$offset + self::ORDER_BATCH_SIZE;
                } else {
                    $this->logger->notice("All orders have been processed.");
                    $this->systemConfigService->set('klavi_overd.cron.fullOrderSyncOffset', -1);
                    $result->addMessage(new Message\InfoMessage('All orders have been processed.'));
                    return $result;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['data' => json_encode($e)]);
                $result->addMessage(new Message\WarningMessage($e->getMessage()));
            }
        }

        $this->systemConfigService->set('klavi_overd.cron.fullOrderSyncOffset', $offset);

        return $result;
    }
}
