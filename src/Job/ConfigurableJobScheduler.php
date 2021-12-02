<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Async\JobExecutionMessage;
use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Specification\StuckJobSpecification;
use Klaviyo\Integration\Job\Exception\JobException;
use Klaviyo\Integration\Job\Exception\JobIsAlreadyRunningException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\MessageBusInterface;

class ConfigurableJobScheduler implements JobSchedulerInterface
{
    private JobHelper $jobHelper;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private StuckJobSpecification $stuckJobSpecification;
    private string $supportedJobType;

    public function __construct(
        JobHelper $jobHelper,
        MessageBusInterface $messageBus,
        LoggerInterface $logger,
        StuckJobSpecification $stuckJobSpecification,
        string $supportedJobType
    ) {
        $this->jobHelper = $jobHelper;
        $this->stuckJobSpecification = $stuckJobSpecification;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->supportedJobType = $supportedJobType;
    }

    public function scheduleJob(Context $context, string $jobType, bool $createdBySchedule = false): void
    {
        try {
            if (!$this->isApplicable($context, $jobType)) {
                throw new JobException(
                    sprintf(
                        'Unsupported job type "%s", scheduler supports "%s"',
                        $jobType,
                        $this->supportedJobType
                    )
                );
            }

            $this->detectStuckSynchronizations($context, $jobType);
            $this->doScheduleJob($context, $jobType, $createdBySchedule);
        } catch (JobIsAlreadyRunningException $exception) {
            throw $exception;
        } catch (JobException $exception) {
            $this->logger
                ->error(
                    sprintf('Schedule Job[type: %s]  Synchronization failed', $jobType),
                    ['exception' => $exception]
                );

            throw $exception;
        } catch (\Throwable $exception) {
            $this->logger
                ->error(
                    sprintf('Schedule Job[type: %s]  Synchronization failed', $jobType),
                    ['exception' => $exception]
                );

            throw new JobException(
                'Schedule Historical events Synchronization failed',
                0,
                $exception
            );
        }
    }

    protected function detectStuckSynchronizations(Context $context, string $jobType)
    {
        $notFinishedSynchronizationJob = $this->jobHelper
            ->getLastActiveJob($context, $jobType);
        if ($notFinishedSynchronizationJob) {
            if (!$this->stuckJobSpecification->isSatisfiedBy($notFinishedSynchronizationJob)) {
                $this->logger->warning(
                    'Not finished synchronization job found',
                    [
                        'id' => $notFinishedSynchronizationJob->getId(),
                        'job' => $notFinishedSynchronizationJob
                    ]
                );

                throw new JobIsAlreadyRunningException('Synchronization is currently running');
            }

            $this->logger->critical(
                'Stuck synchronization job detected',
                ['id' => $notFinishedSynchronizationJob->getId(), 'job' => $notFinishedSynchronizationJob]
            );

            $this->jobHelper
                ->markSynchronizationJobAsStuck($context, $notFinishedSynchronizationJob);
        }
    }

    protected function doScheduleJob(Context $context, string $jobType, bool $createdBySchedule)
    {
        $jobId = $this->jobHelper->createNewJob($context, $jobType, $createdBySchedule);
        $this->sendJobExecutionMessage($jobId);
        $this->jobHelper->tryMarkJobAsPending($context, $jobId);
    }

    /**
     * @param string $jobId
     *
     * @throws JobException
     */
    private function sendJobExecutionMessage(string $jobId)
    {
        try {
            $message = new JobExecutionMessage($jobId);

            $this->messageBus->dispatch($message);
        } catch (\Throwable $throwable) {
            $message = sprintf('Failed to send message to start job[id: %s]', $jobId);
            $this->logger->error($message, ['exception' => $throwable]);

            throw new JobException($message);
        }
    }

    public function isApplicable(Context $context, string $jobType): bool
    {
        return $this->supportedJobType === $jobType;
    }
}