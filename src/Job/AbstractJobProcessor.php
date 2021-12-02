<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Entity\Helper\JobHelper;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\Exception\JobStateIsInvalid;
use Shopware\Core\Framework\Context;

abstract class AbstractJobProcessor implements JobProcessorInterface
{
    protected JobHelper $jobHelper;

    public function __construct(JobHelper $jobHelper)
    {
        $this->jobHelper = $jobHelper;
    }

    public function process(Context $context, JobEntity $job): void
    {
        $this->assertSynchronizationEntity($job);

        $this->jobHelper->markJobAsInProgress($context, $job);

        $isProcessedSuccessfully = $this->doProcess($context, $job);

        if ($isProcessedSuccessfully) {
            $this->jobHelper->markSynchronizationAsSuccess($context, $job);
        } else {
            $this->jobHelper->markSynchronizationJobAsFailed($context, $job);
        }
    }

    /**
     * @param Context $context
     * @param JobEntity $job
     *
     * @return bool
     * @throws \Throwable
     */
    abstract protected function doProcess(Context $context, JobEntity $job): bool;

    protected function assertSynchronizationEntity(JobEntity $job)
    {
        if ($job->getStatus() !== JobEntity::STATUS_PENDING) {
            throw new JobStateIsInvalid(
                sprintf(
                    'Synchronization status invalid. Expected Status %s, Actual status: %s',
                    JobEntity::STATUS_PENDING,
                    $job->getStatus()
                )
            );
        }
        if (!$job->getActive()) {
            throw new JobStateIsInvalid('Synchronization is inactive');
        }
    }
}