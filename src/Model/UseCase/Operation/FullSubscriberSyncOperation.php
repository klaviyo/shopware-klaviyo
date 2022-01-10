<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\OperationResult;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class FullSubscriberSyncOperation
{
    const SUBSCRIBER_BATCH_SIZE = 100;

    private string $parentJobId;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $subscriberRepository;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $subscriberRepository
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->subscriberRepository = $subscriberRepository;
    }

    public function setParentJobId(string $parentJobId): void
    {
        $this->parentJobId = $parentJobId;
    }

    public function execute(Context $context): OperationResult
    {
        $this->doOperation($context);

        return new OperationResult();
    }

    protected function doOperation(Context $context)
    {
        $criteria = new Criteria();
        $criteria->setLimit(self::SUBSCRIBER_BATCH_SIZE);
        $criteria->addFilter(
            new EqualsAnyFilter(
                'status',
                [
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT
                ]
            )
        );
        $iterator = new RepositoryIterator($this->subscriberRepository, $context, $criteria);

        while (($subscriberIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $context,
                $subscriberIds,
                $this->parentJobId
            );
        }
    }
}
