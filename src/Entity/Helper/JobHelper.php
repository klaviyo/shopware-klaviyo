<?php

namespace Klaviyo\Integration\Entity\Helper;

use Klaviyo\Integration\Entity\Job\JobDefinition;
use Klaviyo\Integration\Entity\Job\JobEntity;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class JobHelper
{
    private EntityRepositoryInterface $jobRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $synchronizationsRepository,
        LoggerInterface $logger
    ) {
        $this->jobRepository = $synchronizationsRepository;
        $this->logger = $logger;
    }

    public function createNewJob(Context $context, string $type, bool $createdBySchedule): string
    {
        $createEntityResult = $this->jobRepository->create(
            [
                [
                    'status' => JobEntity::STATUS_NEW,
                    'type' => $type,
                    'active' => true,
                    'createdBySchedule' => $createdBySchedule,
                ]
            ],
            $context
        );
        $keys = $createEntityResult->getPrimaryKeys(JobDefinition::ENTITY_NAME);
        if (empty($keys)) {
            throw new \RuntimeException('Could not create historical event tracking synchronization job entity');
        }

        return reset($keys);
    }

    public function tryMarkJobAsPending(Context $context, string $jobId): void
    {
        try {
            $this->jobRepository->update(
                [
                    [
                        'id' => $jobId,
                        'status' => JobEntity::STATUS_PENDING,
                    ]
                ],
                $context
            );
        } catch (\Throwable $throwable) {
            $this->logger->error(
                sprintf('Failed to change synchronization job[id: %s] status to PENDING', $jobId),
                [
                    'exception' => $throwable
                ]
            );
        }
    }

    public function markSynchronizationJobAsStuck(Context $context, JobEntity $job)
    {
        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'status' => JobEntity::STATUS_STUCK,
                    'finishedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                    'active' => false
                ]
            ],
            $context
        );
    }

    public function tryMarkSynchronizationJobAsFailed(Context $context, JobEntity $job)
    {
        try {
            $this->markSynchronizationJobAsFailed($context, $job);
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Exception happened during change synchronization job[id: %s] status to "failed". Message: %s',
                    $job->getId(),
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception,
                    'context' => $context
                ]
            );
        }
    }

    public function markSynchronizationJobAsFailed(Context $context, JobEntity $job)
    {
        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'status' => JobEntity::STATUS_FAILED,
                    'finishedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                    'active' => false
                ]
            ],
            $context
        );
    }

    public function markSynchronizationAsSuccess(Context $context, JobEntity $job)
    {
        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'status' => JobEntity::STATUS_SUCCESS,
                    'finishedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                    'active' => false
                ]
            ],
            $context
        );
    }

    public function markJobAsInProgress(Context $context, JobEntity $job)
    {
        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'startedAt' => new \DateTime('now', new \DateTimeZone('UTC')),
                    'status' => JobEntity::STATUS_IN_PROGRESS
                ]
            ],
            $context
        );
    }

    public function getLastActiveJob(Context $context, string $type): ?JobEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $jobs = $this->jobRepository->search($criteria, $context);

        return $jobs->first();
    }

    public function hasFinishedJobCreatedByScheduleAfter(
        Context $context,
        string $type,
        \DateTimeInterface $comparisonDate
    ): ?JobEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', false));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->addFilter(new EqualsFilter('createdBySchedule', true));
        $criteria->addFilter(
            new RangeFilter(
                'createdAt',
                [
                    // String used because range filter does not convert \DateTime
                    RangeFilter::GTE => $comparisonDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)
                ]
            ),);

        $jobs = $this->jobRepository->search($criteria, $context);

        return $jobs->first();
    }

    public function getSynchronizationJobById(string $jobId, Context $context): ?JobEntity
    {
        try {
            return $this->jobRepository
                ->search(new Criteria([$jobId]), $context)
                ->first();
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Exception happened during synchronization job fetch. Message: %s', $exception->getMessage()),
                [
                    'jobId' => $jobId,
                    'exception' => $exception,
                    'context' => $context
                ]
            );

            return null;
        }
    }

    public function getLastSuccessJob(Context $context, string $type): ?JobEntity
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->addFilter(new EqualsFilter('status', JobEntity::STATUS_SUCCESS));
        $criteria->addSorting(new FieldSorting('finishedAt', FieldSorting::DESCENDING));
        $criteria->setLimit(1);

        $synchronizationResult = $this->jobRepository->search($criteria, $context);

        /** @var JobEntity $synchronization */
        $synchronization = $synchronizationResult->first();

        return $synchronization;
    }

    public function getLastJob(Context $context, string $type): ?JobEntity
    {
        $criteria = new Criteria();

        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addFilter(new EqualsFilter('type', $type));
        $criteria->setLimit(1);

        $synchronizationResult = $this->jobRepository->search($criteria, $context);

        /** @var JobEntity $synchronization */
        $synchronization = $synchronizationResult->first();

        return $synchronization;
    }

    public function getJobsCount(Context $context, string $type): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', $type));

        $criteria->addAggregation(new CountAggregation('count', 'id'));

        $result = $this->jobRepository->aggregate($criteria, $context)->get('count');
        if ($result === null) {
            throw new InvalidAggregationQueryException('Could not aggregate jobs count');
        }

        return $result->getCount();
    }

    public function removeOldJobs(Context $context, string $type, int $quantityOfRecordsThatMustBeLeft)
    {
        $newestSuccessJobCriteria = new Criteria();
        $newestSuccessJobCriteria->addSorting(new FieldSorting('finishedAt', 'DESC'));
        $newestSuccessJobCriteria->setLimit(1);
        $newestSuccessJobCriteria->addFilter(
            new EqualsFilter('status', JobEntity::STATUS_SUCCESS)
        );
        $newestSuccessJobCriteria->addFilter(
            new EqualsFilter('type', $type)
        );
        $newestSuccessSynchronizationId = $this->jobRepository
            ->searchIds($newestSuccessJobCriteria, $context)
            ->firstId();

        $entitiesToPreserveIdsFetchCriteria = new Criteria();
        $entitiesToPreserveIdsFetchCriteria->addFilter(
            new EqualsFilter('type', $type)
        );
        $entitiesToPreserveIdsFetchCriteria->addSorting(new FieldSorting('finishedAt', 'DESC'));
        $entitiesToPreserveIdsFetchCriteria->setLimit($quantityOfRecordsThatMustBeLeft);
        $entitiesToPreserveIds = $this->jobRepository
            ->searchIds($entitiesToPreserveIdsFetchCriteria, $context)->getIds();
        if ($newestSuccessSynchronizationId) {
            $entitiesToPreserveIds[] = $newestSuccessSynchronizationId;
        }

        $entitiesToRemoveCriteria = new Criteria();
        $entitiesToRemoveCriteria->addFilter(
            new EqualsFilter('type', $type)
        );
        if ($entitiesToPreserveIds) {
            $entitiesToRemoveCriteria->addFilter(
                new NandFilter(
                    [
                        new EqualsAnyFilter(
                            'id',
                            $entitiesToPreserveIds
                        )
                    ]
                )
            );
        }

        $entitiesToRemoveIds = $this->jobRepository->searchIds($entitiesToRemoveCriteria, $context);

        $deleteDataSet = array_map(function ($id) {
            return ['id' => $id];
        }, $entitiesToRemoveIds->getIds());
        $this->jobRepository->delete($deleteDataSet, $context);
    }
}