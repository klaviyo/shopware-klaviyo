<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullOrderSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-order-sync-handler';
    private const ORDER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepository $orderRepository;
    private GetValidChannels $getValidChannels;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepository $orderRepository,
        GetValidChannels $getValidChannels
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
        $this->getValidChannels = $getValidChannels;
    }

    /**
     * @param FullOrderSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $result->addMessage(new Message\InfoMessage('Starting Full Order Sync Operation...'));
        $subOperationCount = 0;

        $channelIds = $this->getValidChannels->execute($message->getContext())->map(fn(SalesChannelEntity $channel) => $channel->getId());
        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $criteria = new Search\Criteria();
        $criteria->addFilter(new Search\Filter\EqualsAnyFilter('salesChannelId', \array_values($channelIds)));
        $criteria->setLimit(self::ORDER_BATCH_SIZE);
        $iterator = new RepositoryIterator($this->orderRepository, $message->getContext(), $criteria);

        while (($orderIds = $iterator->fetchIds()) !== null) {
            $subOperationCount++;
            $this->scheduleBackgroundJob->scheduleOrderSyncJob($orderIds, $message->getJobId(), $message->getContext());
        }

        $result->addMessage(new Message\InfoMessage(\sprintf('Total %s jobs has been scheduled.', $subOperationCount)));

        return $result;
    }
}
