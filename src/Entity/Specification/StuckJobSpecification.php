<?php

namespace Klaviyo\Integration\Entity\Specification;

use Klaviyo\Integration\Utils\Specification\SpecificationInterface;

class StuckJobSpecification implements SpecificationInterface
{
    private int $maximumSyncJobPendingTime;
    private int $maximumSyncJobExecutionTime;

    public function __construct(
        int $maximumSyncJobPendingTime,
        int $maximumSyncJobExecutionTime
    ) {
        $this->maximumSyncJobPendingTime = $maximumSyncJobPendingTime;
        $this->maximumSyncJobExecutionTime = $maximumSyncJobExecutionTime;
    }

    public function isSatisfiedBy($value): bool
    {
        if (SyncPendingLongerThenSpecification::create($this->maximumSyncJobPendingTime)
            ->isSatisfiedBy($value)) {
            return true;
        }

        return SyncWorksLongerThanSpecification::create($this->maximumSyncJobExecutionTime)
            ->isSatisfiedBy($value);
    }
}
