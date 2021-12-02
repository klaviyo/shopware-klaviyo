<?php

namespace Klaviyo\Integration\Job;

use Klaviyo\Integration\Job\Exception\JobException;
use Shopware\Core\Framework\Context;

class VirtualProxyJobScheduler implements JobSchedulerInterface
{
    /**
     * @var array|JobSchedulerInterface[]
     */
    private array $jobSchedulers = [];

    public function __construct(iterable $jobSchedulers)
    {
        foreach ($jobSchedulers as $jobScheduler) {
            $this->addScheduler($jobScheduler);
        }
    }

    public function addScheduler(JobSchedulerInterface $jobScheduler)
    {
        $this->jobSchedulers[] = $jobScheduler;
    }

    public function scheduleJob(Context $context, string $jobType, bool $createdBySchedule = false): void
    {
        $realScheduler = $this->getScheduler($context, $jobType);
        if ($realScheduler === null) {
            throw new JobException(sprintf('Job scheduler for job[type: %s] was not found', $jobType));
        }

        $realScheduler->scheduleJob($context, $jobType, $createdBySchedule);
    }

    public function isApplicable(Context $context, string $jobType): bool
    {
        return null !== $this->getScheduler($context, $jobType);
    }

    private function getScheduler(Context $context, string $jobType): ?JobSchedulerInterface
    {
        foreach ($this->jobSchedulers as $jobScheduler) {
            if ($jobScheduler->isApplicable($context, $jobType)) {
                return $jobScheduler;
            }
        }

        return null;
    }
}