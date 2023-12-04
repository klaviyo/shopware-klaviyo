<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullSubscriberSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-subscriber-sync-handler';
    private const SUBSCRIBER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepository $subscriberRepository;
    private GetValidChannels $getValidChannels;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepository $subscriberRepository,
        GetValidChannels $getValidChannels
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->subscriberRepository = $subscriberRepository;
        $this->getValidChannels = $getValidChannels;
    }

    /**
     * @param FullSubscriberSyncMessage $message
     * @return JobResult
     *
     * @throws \Exception
     */
    public function execute(object $message): JobResult
    {
        $subOperationCount = 0;
        $result = new JobResult();
        $channelIds = $this->getValidChannels->execute(
            $message->getContext())->map(fn(SalesChannelEntity $channel) => $channel->getId()
        );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $criteria = new Criteria();
        $criteria->setLimit(self::SUBSCRIBER_BATCH_SIZE);
        $criteria->addFilter(
            new EqualsAnyFilter(
                'status',
                [
                    NewsletterSubscribeRoute::STATUS_OPT_OUT,
                    NewsletterSubscribeRoute::STATUS_OPT_IN,
                    NewsletterSubscribeRoute::STATUS_DIRECT
                ]
            ),
            new EqualsAnyFilter('salesChannelId', $channelIds)
        );

        $schedulingResult = $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs(
            $message->getContext(),
            $message->getJobId(),
            $channelIds
        );

        $excludedSubscriberIds = [];
        foreach ($schedulingResult->all() as $channelId => $emails) {
            $excludedCriteria = new Criteria();
            $excludedCriteria->addFilter(new EqualsFilter('salesChannelId', $channelId));
            $excludedCriteria->addFilter(new EqualsAnyFilter('email', $emails));
            $excludedSubscriberIds = \array_merge(
                $excludedSubscriberIds,
                \array_values(
                    $this->subscriberRepository->searchIds($excludedCriteria, $message->getContext())->getIds()
                )
            );
        }

        $iterator = new RepositoryIterator($this->subscriberRepository, $message->getContext(), $criteria);

        while (($subscriberIds = $iterator->fetchIds()) !== null) {
            $subscriberIds = \array_values(\array_diff($subscriberIds, $excludedSubscriberIds));
            $subOperationCount++;
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $message->getJobId(),
                $message->getContext()
            );
        }

        $result->addMessage(new Message\InfoMessage(\sprintf('Total %s jobs has been scheduled.', $subOperationCount)));

        foreach ($schedulingResult->getErrors() as $error) {
            $result->addMessage(new Message\ErrorMessage($error->getMessage()));
        }

        return $result;
    }
}
