<?php declare(strict_types=1);

namespace Od\Scheduler\Model;

use Od\Scheduler\Async\JobMessageInterface;
use Od\Scheduler\Entity\Job\JobEntity;
use Od\Scheduler\Model\Job\{HandlerPool, JobHelper};
use Od\Scheduler\Model\Job\GeneratingHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class JobScheduler
{
    private EntityRepository $jobRepository;
    private SerializerInterface $messageSerializer;
    private MessageBusInterface $messageBus;
    private HandlerPool $handlerPool;
    private JobHelper $jobHelper;

    public function __construct(
        EntityRepository $jobRepository,
        SerializerInterface $messageSerializer,
        MessageBusInterface $messageBus,
        HandlerPool $handlerPool,
        JobHelper $jobHelper
    ) {
        $this->jobRepository = $jobRepository;
        $this->messageSerializer = $messageSerializer;
        $this->messageBus = $messageBus;
        $this->handlerPool = $handlerPool;
        $this->jobHelper = $jobHelper;
    }

    public function reschedule(string $jobId)
    {
        $criteria = new Criteria([$jobId]);
        /** @var JobEntity $job */
        $job = $this->jobRepository->search($criteria, Context::createDefaultContext())->first();

        if ($job === null) {
            throw new \Exception(\sprintf('Unable to reschedule job[id: %s]: not found.', $jobId));
        }

        $this->rescheduleJob($job);
    }

    private function rescheduleJob(JobEntity $job)
    {
        $jobMessage = $this->messageSerializer->decode(['body' => $job->getMessage()])->getMessage();

        if (!$jobMessage instanceof JobMessageInterface) {
            throw new \Exception(\sprintf('Unable to reschedule job[id: %s]: wrong message.', $job->getId()));
        }

        $this->jobHelper->markJob($job->getId(), JobEntity::TYPE_PENDING);
        $jobHandler = $this->handlerPool->get($jobMessage->getHandlerCode());

        if ($parentJobId = $job->getParentId()) {
            $this->jobHelper->markJob($parentJobId, JobEntity::TYPE_RUNNING);
        }

        /**
         * In case on generating handler (aka handler creating child jobs),
         * we need to resend childs' messages to message bus.
         */
        if ($jobHandler instanceof GeneratingHandlerInterface) {
            $childJobCollection = $this->jobHelper->getChildJobs($job->getId(), [JobEntity::TYPE_FAILED]);

            /** @var JobEntity $childJob */
            foreach ($childJobCollection as $childJob) {
                $this->rescheduleJob($childJob);
            }
        } else {
            $this->messageBus->dispatch($jobMessage);
        }
    }

    public function schedule(JobMessageInterface $jobMessage)
    {
        $this->messageBus->dispatch($jobMessage);
    }
}
