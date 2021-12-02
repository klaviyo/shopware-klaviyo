<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Job\Exception\JobException;
use Klaviyo\Integration\Job\Exception\JobStateIsInvalid;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;

class JobExecutor
{
    private JobHelper $jobHelper;
    private JobProcessorInterface $jobProcessor;
    private LoggerInterface $logger;

    public function __construct(JobHelper $jobHelper, JobProcessorInterface $jobProcessor, LoggerInterface $logger)
    {
        $this->jobHelper = $jobHelper;
        $this->jobProcessor = $jobProcessor;
        $this->logger = $logger;
    }

    public function executeJob(Context $context, string $jobId)
    {
        $job = $this->jobHelper->getSynchronizationJobById($jobId, $context);
        if (!$job) {
            throw new JobException(sprintf('Could not find synchronization job[id: %s]', $jobId));
        }

        try {
            $this->jobProcessor->process($context, $job);
        } catch (JobStateIsInvalid $exception) {
            $this->logger->error(
                sprintf('Synchronization job %s skipped, reason: %s', $jobId, $exception->getMessage()),
                [
                    'context' => $context
                ]
            );
            return;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Exception happened during the execution of the synchronization job %s', $jobId),
                [
                    'context' => $context
                ]
            );

            $this->jobHelper->tryMarkSynchronizationJobAsFailed($context, $job);

            throw new JobException(
                'Historical events tracking synchronization failed',
                0,
                $exception
            );
        }
    }
}