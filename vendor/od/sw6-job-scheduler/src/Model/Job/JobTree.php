<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

use Od\Scheduler\Entity\Job\JobEntity;

class JobTree implements \IteratorAggregate
{
    private JobEntity $rootJob;
    private array $childJobs;

    public function __construct(JobEntity $rootJob, array $childJobs)
    {
        $this->rootJob = $rootJob;
        $this->childJobs = $childJobs;
    }

    public function getRootJob(): JobEntity
    {
        return $this->rootJob;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->childJobs);
    }
}