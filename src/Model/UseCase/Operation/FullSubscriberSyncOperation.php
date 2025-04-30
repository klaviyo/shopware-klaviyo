<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullSubscriberSyncMessage;
use Klaviyo\Integration\Model\Channel\GetValidChannels;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Klaviyo\Integration\Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult, Message};
use Klaviyo\Integration\System\ConfigService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FullSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-subscriber-sync-handler';
    public const SYNC_SUBSCRIBER_OFFSET_CONFIG_KEY = 'klavi_overd.cron.fullSubscriberSyncOffset';
    private const SUBSCRIBER_BATCH_SIZE = 100;

    public function __construct(
        private readonly ScheduleBackgroundJob $scheduleBackgroundJob,
        private readonly EntityRepository      $subscriberRepository,
        private readonly GetValidChannels      $getValidChannels,
        private readonly LoggerInterface       $logger,
        private readonly SystemConfigService   $systemConfigService,
        private readonly ConfigService   $configService
    ) {
    }

    /**
     * @param FullSubscriberSyncMessage $message
     *
     * @throws \Exception
     */
    public function execute(object $message): JobResult
    {
        $result = new JobResult();
        $channelIds = $this->getValidChannels->execute($message->getContext())->map(
            fn(SalesChannelEntity $channel) => $channel->getId()
        );
        $channelIds = \array_values($channelIds);

        if (empty($channelIds)) {
            $result->addMessage(new Message\WarningMessage('There are no configured channels - skipping.'));

            return $result;
        }

        $offset = $this->configService->getConfigValueWithoutCache(self::SYNC_SUBSCRIBER_OFFSET_CONFIG_KEY);

        $schedulingResult = $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJobs(
            $message->getContext(),
            $message->getJobId(),
            $channelIds
        );

        $excludedSubscriberIds = [];

        foreach ($schedulingResult->getAllSubscribersIds() as $ids) {
            $excludedSubscriberIds = \array_merge(
                $excludedSubscriberIds,
                \array_values(
                    $ids
                )
            );
        }

        do {
            try {
                $criteria = new Criteria();
                $criteria->setLimit(self::SUBSCRIBER_BATCH_SIZE);
                $criteria->setOffset($offset);
                $criteria->addFilter(
                    new EqualsAnyFilter(
                        'status',
                        [
                            NewsletterSubscribeRoute::STATUS_OPT_OUT,
                            NewsletterSubscribeRoute::STATUS_OPT_IN,
                            NewsletterSubscribeRoute::STATUS_DIRECT,
                        ]
                    ),
                    new EqualsAnyFilter('salesChannelId', $channelIds)
                );

                $this->logger->notice("Sub offset : $offset");

                $subscribers = $this->subscriberRepository->search($criteria, $message->getContext());
                $subscriberIds = $subscribers->getIds();
                if (!empty($subscriberIds)) {
                    $subscriberIds = \array_values(\array_diff($subscriberIds, $excludedSubscriberIds));

                    $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                        $subscriberIds,
                        $message->getJobId(),
                        $message->getContext(),
                        self::OPERATION_HANDLER_CODE
                    );
                    $result->addMessage(new Message\InfoMessage(\sprintf('Scheduled job for %d subscribers. Offset: %d', count($subscriberIds), $offset)));
                    $offset = (int)$offset + self::SUBSCRIBER_BATCH_SIZE;
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['data' => json_encode($e)]);
                $result->addMessage(new Message\WarningMessage($e->getMessage()));
                return $result;
            }
        } while (!empty($subscriberIds));

        $this->logger->notice("All subscribers have been processed.");
        $this->systemConfigService->set(self::SYNC_SUBSCRIBER_OFFSET_CONFIG_KEY, -1);
        $result->addMessage(new Message\InfoMessage('All subscribers have been processed.'));

        foreach ($schedulingResult->getErrors() as $error) {
            $result->addMessage(new Message\ErrorMessage($error->getMessage()));
        }

        return $result;
    }
}
