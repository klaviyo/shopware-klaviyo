<?php declare(strict_types=1);

namespace Klaviyo\Integration\Model\UseCase\Operation;

use Klaviyo\Integration\Async\Message\FullSubscriberSyncMessage;
use Klaviyo\Integration\Klaviyo\Client\ApiTransfer\Message\ExcludedSubscribers\GetExcludedSubscribersResponse;
use Klaviyo\Integration\Klaviyo\Gateway\KlaviyoGateway;
use Klaviyo\Integration\Model\UseCase\ScheduleBackgroundJob;
use Od\Scheduler\Model\Job\{GeneratingHandlerInterface, JobHandlerInterface, JobResult};
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class FullSubscriberSyncOperation implements JobHandlerInterface, GeneratingHandlerInterface
{
    public const OPERATION_HANDLER_CODE = 'od-klaviyo-full-subscriber-sync-handler';
    private const SUBSCRIBER_BATCH_SIZE = 100;

    private ScheduleBackgroundJob $scheduleBackgroundJob;
    private EntityRepositoryInterface $subscriberRepository;
    private KlaviyoGateway $klaviyoGateway;
    private EntityRepositoryInterface $salesChannelRepository;

    public function __construct(
        ScheduleBackgroundJob $scheduleBackgroundJob,
        EntityRepositoryInterface $subscriberRepository,
        KlaviyoGateway $klaviyoGateway,
        EntityRepositoryInterface $salesChannelRepository
    ) {
        $this->scheduleBackgroundJob = $scheduleBackgroundJob;
        $this->subscriberRepository = $subscriberRepository;
        $this->klaviyoGateway = $klaviyoGateway;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    /**
     * @param FullSubscriberSyncMessage $message
     * @return JobResult
     */
    public function execute(object $message): JobResult
    {
        $context = Context::createDefaultContext();
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
            )
        );
        $iterator = new RepositoryIterator($this->subscriberRepository, $context, $criteria);

        //TODO set page + hash of the last page -> json -> md5 ->new table
        $context = Context::createDefaultContext();
        /** @var SalesChannelEntity $channel */
        $channels = $this->salesChannelRepository->search(new Criteria(), $context);
        $result = new JobResult();
        foreach ($channels as $channel) {
            try {
                $result = $this->getExcludedSubscribers($channel);
            } catch (\Throwable $e) {
                $result->addError($e);
            }
        }

        foreach ($result->getLists() as $email) {
            $this->scheduleBackgroundJob->scheduleExcludedSubscribersSyncJob($email->getEmail(), $message->getJobId());
        }

        while (($subscriberIds = $iterator->fetchIds()) !== null) {
            $this->scheduleBackgroundJob->scheduleSubscriberSyncJob(
                $subscriberIds,
                $message->getJobId()
            );
        }

        return new JobResult();
    }

    public function getExcludedSubscribers($channel): GetExcludedSubscribersResponse
    {
        return $this->klaviyoGateway->getExcludedSubscribersFromList($channel);
    }
}
