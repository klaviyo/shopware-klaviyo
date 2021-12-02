<?php

namespace Klaviyo\Integration\Entity\Specification;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Utils\Specification\MinutesPassedAfterDateSpecification;
use Klaviyo\Integration\Utils\Specification\SpecificationInterface;

class SyncWorksLongerThanSpecification implements SpecificationInterface
{
    private int $intervalInMinutes;

    public function __construct(int $intervalInMinutes)
    {
        $this->intervalInMinutes = $intervalInMinutes;
    }

    public static function create(int $intervalInMinutes): SyncWorksLongerThanSpecification
    {
        return new self($intervalInMinutes);
    }

    /**
     * @param JobEntity $value
     * {@inheritDoc}
     */
    public function isSatisfiedBy($value): bool
    {
        $startedAt = $value->getStartedAt();
        if (!$startedAt) {
            return false;
        }

        return MinutesPassedAfterDateSpecification::create($this->intervalInMinutes)->isSatisfiedBy($startedAt);
    }
}
