<?php

namespace Klaviyo\Integration\Utils\Specification;

class MinutesPassedAfterDateSpecification implements SpecificationInterface
{
    /**
     * Determines how much minutes should be passed after the date passed into "isSatisfiedBy" method
     * in order to satisfy this specification
     *
     * @var int
     */
    private int $minutesCount;

    public function __construct(int $minutesCount)
    {
        $this->minutesCount = $minutesCount;
    }

    public static function create(int $minutesCount): MinutesPassedAfterDateSpecification
    {
        return new self($minutesCount);
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     *
     * @return bool
     */
    public function isSatisfiedBy($value): bool
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $beforeDate = $now->sub(new \DateInterval(sprintf('PT%sM', $this->minutesCount)));

        return $value <= $beforeDate;
    }
}
