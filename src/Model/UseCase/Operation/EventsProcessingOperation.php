<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\EventsProcessingMessage;
use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Event\EventEntity;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\System\Tracking\EventsTrackerInterface;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Od\Scheduler\Model\Job\Message;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class EventsProcessingOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const HANDLER_CODE = 'od-klaviyo-events-sync-handler';
    public const REALTIME_SUBSCRIBERS_OPERATION_LABEL = 'real-time-subscribers-sync-operation';
    private const LAST_EXECUTION_TIME_CONFIG = 'klavi_overd.config.dailySyncLastTime';
    private const DATE_FORMAT = 'Y-m-d';

    private EntityRepository $eventRepository;
    private EntityRepository $cartEventRequestRepository;
    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private GetValidChannels $getValidChannels;
    private ConfigurationRegistry $configurationRegistry;
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    private JobResult $jobResult;

    public function __construct(
        EntityRepository $eventRepository,
        EntityRepository $cartEventRequestRepository,
        ScheduleBackgroundJob $scheduleBackgroundJob,
        GetValidChannels $getValidChannels,
        ConfigurationRegistry $configurationRegistry,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartEventRequestRepository = $cartEventRequestRepository;
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->getValidChannels = $getValidChannels;
        $this->configurationRegistry = $configurationRegistry;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @param EventsProcessingMessage $message
     *
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $this->jobResult = new JobResult();
        $context = $message->getContext();

        $channelIds = $this->getValidChannels->execute($context)->map(
            fn (SalesChannelEntity $channel) => $channel->getId()
        );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $this->jobResult->addMessage(
                new Message\WarningMessage('There are no configured channels - skipping.')
            );

            return $this->jobResult;
        }

        $this->processOrderEvents($context, $message->getJobId(), $channelIds);
        $this->processCartEvents($context, $message->getJobId(), $channelIds);
        $this->processCustomerProfileEvents($context, $message->getJobId(), $channelIds);
        $this->processSubscriberEvents($context, $message->getJobId(), $channelIds);

        $this->processFullSubscriberSyncByTimeEvent($context, $channelIds);

        return $this->jobResult;
    }

    private function processCustomerProfileEvents(Context $context, string $parentJobId, array $channelIds): void
    {
        $total = 0;
        $iterator = $this->getEventRepoIterator(
            $context,
            [EventsTrackerInterface::CUSTOMER_WRITTEN_EVENT],
            $channelIds
        );

        while (($events = $iterator->fetch()) !== null) {
            $customerIds = $events->map(fn (EventEntity $event) => $event->getEntityId());
            $customerIds = array_values(array_unique($customerIds));
            $total += \count($customerIds);
            $this->scheduleBackgroundJob->scheduleCustomerProfilesSyncJob($customerIds, $parentJobId, $context);
            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s customer events was scheduled.', $total))
            );
        }
    }

    private function processCartEvents(Context $context, string $parentJobId, array $channelIds): void
    {
        $total = 0;
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $channelIds));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);
        $iterator = new RepositoryIterator($this->cartEventRequestRepository, $context, $criteria);

        while (($eventRequestIds = $iterator->fetchIds()) !== null) {
            $total += \count($eventRequestIds);
            $this->scheduleBackgroundJob->scheduleCartEventsSyncJob($eventRequestIds, $parentJobId, $context);
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s cart events was scheduled.', $total))
            );
        }
    }

    private function processOrderEvents(Context $context, string $parentJobId, array $channelIds): void
    {
        $total = 0;
        $iterator = $this->getEventRepoIterator(
            $context,
            \array_keys(EventsTrackerInterface::ORDER_EVENTS),
            $channelIds
        );

        while (($eventIds = $iterator->fetchIds()) !== null) {
            $total += \count($eventIds);
            $this->scheduleBackgroundJob->scheduleOrderEventsSyncJob($eventIds, $parentJobId, $context);
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s order events was scheduled.', $total))
            );
        }
    }

    private function processSubscriberEvents(
        Context $context,
        string $parentJobId,
        array $channelIds
    ): void {
        $total = 0;

        $iterator = $this->getEventRepoIterator($context, EventsTrackerInterface::SUBSCRIBER_EVENTS, $channelIds);

        while (($events = $iterator->fetch()) !== null) {
            $subscriberIds = $events->map(fn (EventEntity $event) => $event->getEntityId());
            $total += \count($subscriberIds);
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $parentJobId,
                $context,
                self::REALTIME_SUBSCRIBERS_OPERATION_LABEL
            );
            $this->deleteProcessedEvents($context, $events->getEntities());
        }

        if ($total > 0) {
            $this->jobResult->addMessage(
                new Message\InfoMessage(\sprintf('Total %s subscriber events was scheduled.', $total))
            );
        }
    }

    private function deleteProcessedEvents(Context $context, EntityCollection $events): void
    {
        $deleteDataSet = array_map(function ($id) {
            return ['id' => $id];
        }, array_values($events->getIds()));
        $this->eventRepository->delete($deleteDataSet, $context);
    }

    private function getEventRepoIterator(Context $context, array $eventTypes, array $channelIds): RepositoryIterator
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('type', $eventTypes),
            new EqualsAnyFilter('salesChannelId', $channelIds)
        );
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->setLimit(100);

        return new RepositoryIterator($this->eventRepository, $context, $criteria);
    }

    private function processFullSubscriberSyncByTimeEvent(Context $context, array $channelIds): void
    {
        $isJobStarted = false;

        try {
            foreach ($channelIds as $channelId) {
                if ($this->isTimeToRunJob($channelId)) {
                    $isJobStarted = true;
                    $this->scheduleBackgroundJob->scheduleFullSubscriberSyncJob($context);

                    $this->systemConfigService->set(
                        self::LAST_EXECUTION_TIME_CONFIG,
                        (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Unable to sync job, the reason is:'.$e->getMessage());
        }

        if ($isJobStarted) {
            $this->jobResult->addMessage(
                new Message\InfoMessage('Full subscribers sync event was scheduled.')
            );
        }
    }

    private function isTimeToRunJob(string $channelId = null): bool
    {
        $configuration = $this->configurationRegistry->getConfiguration($channelId);

        if (!$configuration->isDailySubscribersSynchronization()) {
            return false;
        }

        if ($this->isTodayAlreadyRun($channelId)) {
            return false;
        }

        try {
            $executionTime = new \DateTime($configuration->getDailySubscribersSyncTime());
        } catch (\Exception) {
            return false;
        }

        return $executionTime <= new \DateTime();
    }

    private function isTodayAlreadyRun(string $channelId = null): bool
    {
        $lastSyncTime = $this->systemConfigService->get(
            self::LAST_EXECUTION_TIME_CONFIG,
            $channelId
        );

        if (empty($lastSyncTime)) {
            return false;
        }

        try {
            $lastSyncTimeObject = new \DateTime($lastSyncTime);
        } catch (\Exception) {
            return false;
        }

        return $lastSyncTimeObject->format(self::DATE_FORMAT) === (new \DateTime())->format(self::DATE_FORMAT);
    }
}
