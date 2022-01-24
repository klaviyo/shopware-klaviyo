<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullOrderSyncMessage;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult};
use Od\Scheduler\Model\MessageManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class FullOrderSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-order-sync-handler';
    private const ORDER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $orderRepository;
    private MessageManager $messageManager;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $orderRepository,
        MessageManager $messageManager
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @param FullOrderSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $subOperationCount = 0;
        $this->messageManager->addInfoMessage($message->getJobId(), 'Starting Full Order Sync Operation...');
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(self::ORDER_BATCH_SIZE);
        $iterator = new RepositoryIterator($this->orderRepository, $context, $criteria);

        while (($orderIds = $iterator->fetchIds()) !== null) {
            $subOperationCount++;
            $this->scheduleBackgroundJob->scheduleOrderSyncJob($orderIds, $message->getJobId());
        }

        $this->messageManager->addInfoMessage(
            $message->getJobId(),
            \sprintf('Total %s jobs has been scheduled.', $subOperationCount)
        );

        return new JobResult();
    }
}
