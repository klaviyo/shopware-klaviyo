<?php

namespace Klaviyo\Integration\Tests\Unit\Entity\Specification;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Entity\Specification\StuckJobSpecification;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

class StuckJobSpecificationTest extends TestCase
{
    use KernelTestBehaviour;

    protected StuckJobSpecification $specification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specification = $this->getContainer()
            ->get('klaviyo.tracking_integration.subscribers_synchronization.job.stuck.specification');
    }

    public function testJobNotStuckBecauseItIsFinished()
    {
        $job = new JobEntity();

        $job->setActive(false);
        $job->setStatus(JobEntity::STATUS_FAILED);

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertFalse($actual);

        $job->setStatus(JobEntity::STATUS_SUCCESS);

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertFalse($actual);
    }

    public function testJobNotStuckBecauseNotStartedButNotEnoughTimePassed()
    {
        $job = new JobEntity();

        $job->setActive(true);
        $job->setStatus(JobEntity::STATUS_PENDING);
        $job->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertFalse($actual);

        $job->setStatus(JobEntity::STATUS_NEW);
        $actual = $this->specification->isSatisfiedBy($job);
        self::assertFalse($actual);
    }

    public function testJobStuckBecauseNotStarted()
    {
        $job = new JobEntity();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $oneHourAgo = $now->modify('-1 hour');

        $job->setActive(true);
        $job->setStatus(JobEntity::STATUS_PENDING);
        $job->setCreatedAt($oneHourAgo);

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertTrue($actual);

        $job->setStatus(JobEntity::STATUS_NEW);
        $actual = $this->specification->isSatisfiedBy($job);
        self::assertTrue($actual);
    }

    public function testJobNotStuckBecauseStartedButProcessedNotLongEnough()
    {
        $job = new JobEntity();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $oneHourAgo = $now->modify('-1 hour');

        $job->setActive(true);
        $job->setStatus(JobEntity::STATUS_IN_PROGRESS);
        $job->setCreatedAt($oneHourAgo);
        $job->setStartedAt($oneHourAgo);

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertFalse($actual);
    }

    public function testJobStuckBecauseProcessedTooLong()
    {
        $job = new JobEntity();

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $oneHourAgo = $now->modify('-1 hour');
        $fiveHourAgo = $now->modify('-5 hour');

        $job->setActive(true);
        $job->setStatus(JobEntity::STATUS_IN_PROGRESS);
        $job->setCreatedAt($oneHourAgo);
        $job->setStartedAt($fiveHourAgo);

        $actual = $this->specification->isSatisfiedBy($job);
        self::assertTrue($actual);
    }
}