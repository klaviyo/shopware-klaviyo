<?php declare(strict_types=1);

namespace Od\Scheduler\Model\Job;

use Od\Scheduler\Entity\Job\JobCollection;
use Od\Scheduler\Entity\Job\JobEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class JobHelper
{
    private EntityRepositoryInterface $jobRepository;

    public function __construct(EntityRepositoryInterface $jobRepository)
    {
        $this->jobRepository = $jobRepository;
    }

    public function deleteJob(string $jobId)
    {
        $this->jobRepository->delete(
            [['id' => $jobId]],
            Context::createDefaultContext()
        );
    }

    public function markJob(string $jobId, string $status)
    {
        $jobData = [
            'id' => $jobId,
            'status' => $status,
        ];

        if ($status === JobEntity::TYPE_PENDING) {
            $jobData['startedAt'] = null;
            $jobData['finishedAt'] = null;
        } else {
            $timeKey = $status === JobEntity::TYPE_RUNNING ? 'startedAt' : 'finishedAt';
            $jobData[$timeKey] = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        $this->jobRepository->update(
            [$jobData],
            Context::createDefaultContext()
        );
    }

    public function getChildJobs(string $parentJobId, array $statuses = []): JobCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentJobId));

        if (!empty($statuses)) {
            $criteria->addFilter(new EqualsAnyFilter('status', $statuses));
        }

        /** @var JobCollection $jobs */
        $jobs = $this->jobRepository->search($criteria, Context::createDefaultContext())->getEntities();

        return $jobs;
    }
}
