<?php

namespace Klaviyo\Integration\Tests\Constraint;

use Klaviyo\Integration\Entity\Job\JobEntity;
use PHPUnit\Framework\Assert;

class JobMatchConstraint extends AbstractMultipleAssertConstraint
{
    private const CREATED_AT = 'createdAt';
    private const UPDATED_AT = 'updatedAt';
    private const STARTED_AT = 'startedAt';
    private const FINISHED_AT = 'finishedAt';
    private const ACTIVE = 'active';
    private const STATUS = 'status';
    private const TYPE = 'type';

    private ComparableRepresentationDataConverter $comparableRepresentationDataConverter;

    private ?bool $isActive;
    private ?string $status;
    private ?string $type;
    private ?\DateTimeInterface $startedAt;
    private ?\DateTimeInterface $finishedAt;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(
        ?bool $isActive,
        ?string $status,
        ?string $type,
        ?\DateTimeInterface $startedAt,
        ?\DateTimeInterface $finishedAt,
        ?\DateTimeInterface $createdAt,
        ?\DateTimeInterface $updatedAt
    ) {
        $this->isActive = $isActive;
        $this->status = $status;
        $this->type = $type;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;

        $this->comparableRepresentationDataConverter = new ComparableRepresentationDataConverter();
    }

    public function toString(): string
    {
        return 'Is Historical event tracking data match';
    }

    /**
     * @param JobEntity|null $other
     *
     * @return bool
     */
    protected function doMatch($other): bool
    {
        Assert::assertNotNull($other, 'Historical event tracking is null');
        Assert::assertEquals(
            $this->isActive,
            $other->getActive(),
            'Historical event tracking "active" field value mismatch'
        );
        Assert::assertEquals(
            $this->status,
            $other->getStatus(),
            'Historical event tracking "status" field value mismatch'
        );
        Assert::assertEquals(
            $this->type,
            $other->getType(),
            'Historical event tracking "type" field value mismatch'
        );
        static::assertDateTime(
            $this->createdAt,
            $other->getCreatedAt(),
            'Historical event tracking "createdAt" field value mismatch'
        );
        static::assertDateTime(
            $this->updatedAt,
            $other->getUpdatedAt(),
            'Historical event tracking "updatedAt" field value mismatch'
        );
        static::assertDateTime(
            $this->startedAt,
            $other->getStartedAt(),
            'Historical event tracking "startedAt" field value mismatch'
        );
        static::assertDateTime(
            $this->finishedAt,
            $other->getFinishedAt(),
            'Historical event tracking "finishedAt" field value mismatch'
        );

        return true;
    }

    public function prepareExpectedValue(): ?array
    {
        return [
            self::CREATED_AT => $this->comparableRepresentationDataConverter->convertDateTime($this->createdAt),
            self::UPDATED_AT => $this->comparableRepresentationDataConverter->convertDateTime($this->updatedAt),
            self::STARTED_AT => $this->comparableRepresentationDataConverter->convertDateTime($this->startedAt),
            self::FINISHED_AT => $this->comparableRepresentationDataConverter->convertDateTime($this->finishedAt),
            self::ACTIVE => $this->comparableRepresentationDataConverter->convertBool($this->isActive),
            self::STATUS => $this->comparableRepresentationDataConverter->convertString($this->status),
            self::TYPE => $this->comparableRepresentationDataConverter->convertString($this->type),
        ];
    }

    /**
     * @param JobEntity|null $other
     *
     * @return array|null
     */
    public static function prepareActualValue($other): ?array
    {
        if (!$other) {
            return null;
        }

        $dataConverted = new ComparableRepresentationDataConverter();

        return [
            self::CREATED_AT => $dataConverted->convertDateTime($other->getCreatedAt()),
            self::UPDATED_AT => $dataConverted->convertDateTime($other->getUpdatedAt()),
            self::STARTED_AT => $dataConverted->convertDateTime($other->getStartedAt()),
            self::FINISHED_AT => $dataConverted->convertDateTime($other->getFinishedAt()),
            self::ACTIVE => $dataConverted->convertBool($other->getActive()),
            self::STATUS => $dataConverted->convertString($other->getStatus()),
            self::TYPE => $dataConverted->convertString($other->getType()),
        ];
    }
}
