<?php

namespace Klaviyo\Integration\Tests\Job;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Job\VirtualProxyJobScheduler;
use Klaviyo\Integration\Tests\AbstractTestCase;
use Klaviyo\Integration\Tests\Constraint\JobsListMatchConstraint;
use Klaviyo\Integration\Tests\Constraint\JobMatchConstraint;
use Klaviyo\Integration\Job\Exception\JobIsAlreadyRunningException;
use Shopware\Core\Framework\Context;

class JobSchedulerTest extends AbstractTestCase
{
    private VirtualProxyJobScheduler $jobScheduler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobScheduler = $this->getContainer()
            ->get(VirtualProxyJobScheduler::class);
    }

    public function testScheduleCreateNewJobOfTheGivenType()
    {
        $this->jobScheduler
            ->scheduleJob(Context::createDefaultContext(), JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $constraint = new JobsListMatchConstraint(
            [
                new JobMatchConstraint(
                    true,
                    JobEntity::STATUS_PENDING,
                    JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE,
                    null,
                    null,
                    $now,
                    $now
                )
            ]
        );

        $jobs = $this->getJobs();
        self::assertThat($jobs->getElements(), $constraint);

        $this->jobScheduler
            ->scheduleJob(Context::createDefaultContext(), JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE);

        $constraint = new JobsListMatchConstraint(
            [
                new JobMatchConstraint(
                    true,
                    JobEntity::STATUS_PENDING,
                    JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE,
                    null,
                    null,
                    $now,
                    $now
                ),
                new JobMatchConstraint(
                    true,
                    JobEntity::STATUS_PENDING,
                    JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE,
                    null,
                    null,
                    $now,
                    $now
                ),
            ]
        );

        $jobs = $this->getJobs();
        self::assertThat($jobs->getElements(), $constraint);
    }

    /**
     * @dataProvider jobTypesDataProvider
     */
    public function testScheduleSendNewEventRequestIfPreviousStuck($jobType)
    {
        $this->createStuckSynchronization($jobType);
        $this->jobScheduler->scheduleJob(Context::createDefaultContext(), $jobType);
        $stuckJob = $this->getSingleJob();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $constraint = new JobsListMatchConstraint(
            [
                new JobMatchConstraint(
                    false,
                    JobEntity::STATUS_STUCK,
                    $jobType,
                    $stuckJob->getStartedAt(),
                    $now,
                    $now,
                    $now
                ),
                new JobMatchConstraint(
                    true,
                    JobEntity::STATUS_PENDING,
                    $jobType,
                    null,
                    null,
                    $now,
                    $now
                )
            ]
        );

        $jobs = $this->getJobs();
        self::assertThat($jobs->getElements(), $constraint);
    }

    /**
     * @dataProvider jobTypesDataProvider
     */
    public function testScheduleDoNotScheduleAnotherJobIfThereAreOneThatStillRunning($jobType)
    {
        $this->jobScheduler->scheduleJob(Context::createDefaultContext(), $jobType);
        $job = $this->getSingleJob();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'status' => JobEntity::STATUS_IN_PROGRESS,
                    'startedAt' => $now
                ]
            ],
            Context::createDefaultContext()
        );

        $exception = null;
        try {
            $this->jobScheduler->scheduleJob(Context::createDefaultContext(), $jobType);
        } catch (\Throwable $throwable) {
            $exception = $throwable;
        }

        $constraint = new JobsListMatchConstraint(
            [
                new JobMatchConstraint(
                    true,
                    JobEntity::STATUS_IN_PROGRESS,
                    $jobType,
                    $now,
                    null,
                    $now,
                    $now
                )
            ]
        );

        $jobs = $this->getJobs();
        self::assertThat($jobs->getElements(), $constraint);

        self::assertInstanceOf(JobIsAlreadyRunningException::class, $exception);
        self::assertEquals('Synchronization is currently running', $exception->getMessage());
    }

    public function jobTypesDataProvider()
    {
        return [
            'Test with Historical events synchronization job type' => [
                JobEntity::HISTORICAL_EVENTS_SYNCHRONIZATION_TYPE
            ],
            'Test with Subscribers synchronization job type' => [
                JobEntity::SUBSCRIBERS_SYNCHRONIZATION_TYPE
            ],
        ];
    }

    private function createStuckSynchronization(string $jobType)
    {
        $this->jobScheduler->scheduleJob(Context::createDefaultContext(), $jobType);

        $job = $this->getSingleJob();

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $fiveHourAgo = $now->modify('-5 hour');

        $this->jobRepository->update(
            [
                [
                    'id' => $job->getId(),
                    'status' => JobEntity::STATUS_IN_PROGRESS,
                    'startedAt' => $fiveHourAgo
                ]
            ],
            Context::createDefaultContext()
        );
    }
}