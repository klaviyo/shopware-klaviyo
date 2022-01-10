<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\OperationResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class FullOrderSyncOperation
{
    const ORDER_BATCH_SIZE = 100;

    private string $parentJobId;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $orderRepository;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $orderRepository
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->orderRepository = $orderRepository;
    }

    public function setParentJobId(string $parentJobId): void
    {
        $this->parentJobId = $parentJobId;
    }

    public function execute(Context $context): OperationResult
    {
        $criteria = new Criteria();
        $criteria->setLimit(self::ORDER_BATCH_SIZE);
        $iterator = new RepositoryIterator($this->orderRepository, $context, $criteria);

        while (($orderIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleOrderSyncJob(
                $context,
                $orderIds,
                $this->parentJobId
            );
        }

        return new OperationResult();
    }
}
