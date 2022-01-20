<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullOrderSyncMessage;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult};
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

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param FullOrderSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(self::ORDER_BATCH_SIZE);
        $iterator = new RepositoryIterator($this->orderRepository, $context, $criteria);

        while (($orderIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderSyncJob($orderIds, $message->getJobId());
        }

        return new JobResult();
    }
}
