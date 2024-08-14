<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\OrderSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\{EntityRepositoryInterface, Search};
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-order-sync-handler';
    private const ORDER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $orderRepository;
    private GetValidChannels $getValidChannels;
    private LoggerInterface $logger;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $orderRepository,
        GetValidChannels $getValidChannels,
        LoggerInterface $logger
    )
    {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
        $this->getValidChannels = $getValidChannels;
        $this->logger = $logger;
    }

    /**
     * @param OrderSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $this->logger->notice("Starting Partial Order Sync Operation...");
        $result = new JobResult();
        $result->addMessage(new Message\InfoMessage('Starting Partial Order Sync Operation...'));

        try {

            $channelIds = $this->getValidChannels->execute($message->getContext())
                ->map(fn(SalesChannelEntity $channel) => $channel->getId());

            if (empty($channelIds)) {
                $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));
                return $result;
            }

            $offset = $message->getOffset() ?? 0;

            $criteria = new Search\Criteria();
            $criteria->addFilter(new Search\Filter\EqualsAnyFilter('salesChannelId', \array_values($channelIds)));
            $criteria->setLimit(self::ORDER_BATCH_SIZE);
            $criteria->setOffset($offset);

            $iterator = new RepositoryIterator($this->orderRepository, $message->getContext(), $criteria);

            for ($i = 0; $i < 10; $i++) {
                $orderIds = $iterator->fetchIds();
                if (!empty($orderIds)) {
                    $this->scheduleBackgroundJob->scheduleOrderSync($orderIds, $message->getJobId(), $message->getContext());
                    $result->addMessage(new Message\InfoMessage(\sprintf('Scheduled job for %d orders. Offset: %d', count($orderIds), $offset)));
                    $offset += self::ORDER_BATCH_SIZE;
                } else {
                    $result->addMessage(new Message\InfoMessage('All orders have been processed.'));
                    $this->logger->notice("All orders have been processed.");
                    return $result;
                }
            }

            $this->scheduleBackgroundJob->scheduleFullOrderSyncJobPart($message->getContext(), $offset);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['data' => json_encode($e)]);
        }

        return $result;
    }
}
