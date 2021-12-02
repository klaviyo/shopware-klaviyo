<?php

namespace Klaviyo\Integration\Tests\Constraint;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;

abstract class AbstractMultipleAssertConstraint extends Constraint
{
    protected ?string $lastFailureReason = null;

    protected function matches($other): bool
    {
        $this->lastFailureReason = null;

        try {
            return $this->doMatch($other);
        } catch (\Throwable $exception) {
            $this->lastFailureReason = $exception->getMessage();

            return false;
        }
    }

    abstract protected function doMatch($other): bool;

    protected function fail($other, $description, ComparisonFailure $comparisonFailure = null): void
    {
        if (!$comparisonFailure) {
            $comparisonFailure = $this->prepareComparisonFailure($other);
        }

        if (!$this->lastFailureReason) {
            $failureDescription = $this->failureDescription($other);
        } else {
            $failureDescription = $this->lastFailureReason;
        }

        throw new ExpectationFailedException($failureDescription, $comparisonFailure);
    }

    protected function prepareComparisonFailure($other): ComparisonFailure
    {
        $expectedValue = $this->prepareExpectedValue();
        $actualValue = static::prepareActualValue($other);

        return new ComparisonFailure(
            $expectedValue,
            $actualValue,
            print_r($expectedValue, true),
            print_r($actualValue, true),
        );
    }

    protected static function assertDateTime(
        ?\DateTimeInterface $expected,
        ?\DateTimeInterface $actual,
        ?string $message = null
    ) {
        /**
         * @see \SebastianBergmann\Comparator\DateTimeComparator::assertEquals
         */
        $possibleDeltaBetweenDateTimesInSeconds = 60;

        Assert::assertEqualsWithDelta($expected, $actual, $possibleDeltaBetweenDateTimesInSeconds, $message);
    }

    abstract public function prepareExpectedValue(): ?array;

    abstract public static function prepareActualValue($other): ?array;
}
