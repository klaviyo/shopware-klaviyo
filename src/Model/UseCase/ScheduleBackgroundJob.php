<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase;

use Klaviyo\Integration\Async\Message;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\System\Job\BackgroundJobFactory;
use Klaviyo\Integration\System\Job\SchedulerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\Envelope;

class ScheduleBackgroundJob
{
    private SchedulerInterface $scheduler;
    private BackgroundJobFactory $jobFactory;

    public function __construct(
        SchedulerInterface $scheduler,
        ?BackgroundJobFactory $jobFactory = null
    ) {
        $this->scheduler = $scheduler;
        $this->jobFactory = $jobFactory ?? new BackgroundJobFactory();
    }

    public function scheduleFullSubscriberSyncJob(Context $context): JobEntity
    {
        $newJob = $this->jobFactory->create(
            'Full subscribers synchronisation job',
            JobEntity::TYPE_FULL_SUBSCRIBER_SYNC
        );

        $jobMessage = new Message\FullSubscriberSyncMessage();
        $jobMessage->setJobId($newJob->getId());

        return $this->scheduler->scheduleJob($context, $newJob, Envelope::wrap($jobMessage));
    }

    public function scheduleSubscriberSyncJob(
        Context $context,
        array $subscriberIds,
        ?string $parentJobId = null
    ): JobEntity {
        $newJob = $this->jobFactory->create(
            'Subscribers synchronisation job',
            JobEntity::TYPE_SUBSCRIBER_SYNC
        );

        if ($parentJobId !== null) {
            $newJob->setParentId($parentJobId);
        }

        $jobMessage = new Message\SubscriberSyncMessage();
        $jobMessage->setJobId($newJob->getId());
        $jobMessage->setSubscriberIds($subscriberIds);

        return $this->scheduler->scheduleJob($context, $newJob, Envelope::wrap($jobMessage));
    }

    public function scheduleFullOrderSyncJob(Context $context, array $salesChannelIds = []): JobEntity
    {
        $newJob = $this->jobFactory->create(
            'Full orders synchronisation job',
            JobEntity::TYPE_FULL_ORDERS_SYNC
        );

        $jobMessage = new Message\FullOrderSyncMessage();
        $jobMessage->setSalesChannelIds($salesChannelIds);
        $jobMessage->setJobId($newJob->getId());

        return $this->scheduler->scheduleJob($context, $newJob, Envelope::wrap($jobMessage));
    }

    public function scheduleOrderSyncJob(
        Context $context,
        array $orderIds,
        ?string $parentJobId = null
    ): JobEntity {
        $newJob = $this->jobFactory->create('Orders synchronisation job', JobEntity::TYPE_ORDERS_SYNC);

        if ($parentJobId !== null) {
            $newJob->setParentId($parentJobId);
        }

        $jobMessage = new Message\OrderSyncMessage();
        $jobMessage->setJobId($newJob->getId());
        $jobMessage->setOrderIds($orderIds);

        return $this->scheduler->scheduleJob($context, $newJob, Envelope::wrap($jobMessage));
    }

    public function scheduleOrderEventsSyncJob(Context $context, array $eventIds)
    {
        $newJob = $this->jobFactory->create(
            'Orders events synchronisation job',
            JobEntity::TYPE_ORDERS_EVENTS_SYNC
        );

        $jobMessage = new Message\OrderEventSyncMessage();
        $jobMessage->setJobId($newJob->getId());
        $jobMessage->setEventIds($eventIds);

        return $this->scheduler->scheduleJob($context, $newJob, Envelope::wrap($jobMessage));
    }
}
