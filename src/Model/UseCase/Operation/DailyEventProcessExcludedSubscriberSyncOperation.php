<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Od\Scheduler\Model\Job\JobHandlerInterface;
use Od\Scheduler\Model\Job\JobResult;
use Od\Scheduler\Model\Job\Message;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class DailyEventProcessExcludedSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-daily-excluded-subscriber-sync-handler';

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private GetValidChannels $getValidChannels;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        GetValidChannels $getValidChannels
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->getValidChannels = $getValidChannels;
    }

    public function execute(object $message): JobResult
    {
        $jobResult = new JobResult();
        $context = $message->getContext();

        $channelIds = $this->getValidChannels->execute($context)->map(
            fn (SalesChannelEntity $channel) => $channel->getId()
        );

        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $jobResult->addMessage(
                new Message\WarningMessage('There are no configured channels - skipping.')
            );

            return $jobResult;
        }

        $result = $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs(
            $context,
            $message->getJobId(),
            $channelIds
        );

        $subOperationCount = 1;

        if (count($result->getAllSubscribersIds()) > 0) {
            $subOperationCount = count($result->getAllSubscribersIds());
        }

        $jobResult->addMessage(
            new Message\InfoMessage(\sprintf('Total %s job has been scheduled.', $subOperationCount))
        );

        return $jobResult;
    }
}
