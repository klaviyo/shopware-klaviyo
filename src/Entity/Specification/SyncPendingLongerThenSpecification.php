<?php

namespace Klaviyo\Integration\Entity\Specification;

use Klaviyo\Integration\Entity\Job\JobEntity;
use Klaviyo\Integration\Utils\Specification\MinutesPassedAfterDateSpecification;
use Klaviyo\Integration\Utils\Specification\SpecificationInterface;

class SyncPendingLongerThenSpecification implements SpecificationInterface
{
    private int $intervalInMinutes;

    public function __construct(int $intervalInMinutes)
    {
        $this->intervalInMinutes = $intervalInMinutes;
    }

    public static function create(int $intervalInMinutes): SyncPendingLongerThenSpecification
    {
        return new self($intervalInMinutes);
    }

    /**
     * @param JobEntity $value
     * {@inheritDoc}
     */
    public function isSatisfiedBy($value): bool
    {
        if ($value->isStarted()) {
            return false;
        }

        if (!$value->getCreatedAt()) {
            return true;
        }

        return MinutesPassedAfterDateSpecification::create($this->intervalInMinutes)
            ->isSatisfiedBy($value->getCreatedAt());
    }
}
