<?php

namespace Klaviyo\Integration\Tests\Constraint;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;

abstract class AbstractListMatchConstraint extends AbstractMultipleAssertConstraint
{
    /**
     * @var AbstractMultipleAssertConstraint[]
     */
    private array $listItemConstraints;

    public function __construct(array $listItemConstraints)
    {
        $this->assertListItemConstraints($listItemConstraints);
        $this->listItemConstraints = $listItemConstraints;
    }

    protected function doMatch($other): bool
    {
        Assert::assertIsArray($other, 'Expected comparable entities data to be an array');
        Assert::assertCount(
            count($this->listItemConstraints),
            $other,
            'Number of expected and actual entities mismatch'
        );

        reset($other);
        foreach ($this->listItemConstraints as $constraint) {
            $actual = current($other);
            $constraint->evaluate($actual);

            next($other);
        }

        return true;
    }

    protected function failureDescription($other): string
    {
        if (!$this->lastFailureReason) {
            return parent::failureDescription($other);
        } else {
            return $this->lastFailureReason;
        }
    }

    public function prepareExpectedValue(): ?array
    {
        $expectedData = [];
        foreach ($this->listItemConstraints as $constraint) {
            $expectedData[] = $constraint->prepareExpectedValue();
        }

        return $expectedData;
    }

    public static function prepareActualValue($other): ?array
    {
        if (!is_array($other)) {
            return [];
        }

        $itemConstraintClassName = static::getListItemConstraintClassName();
        if (!is_a($itemConstraintClassName, AbstractMultipleAssertConstraint::class, true)) {
            throw new \LogicException(
                'List item constraint class must be extended from the '. AbstractMultipleAssertConstraint::class
            );
        }

        $actualValues = [];
        foreach ($other as $item) {
            $actualValues[] = call_user_func([$itemConstraintClassName, 'prepareActualValue'], $item);
        }

        return $actualValues;
    }

    protected function fail($other, $description, ComparisonFailure $comparisonFailure = null): void
    {
        if (!$comparisonFailure) {
            $comparisonFailure = $this->prepareComparisonFailure($other);
        }

        if ($this->lastFailureReason) {
            $failureDescription = sprintf(
                'Failed assert that actual list of items match expected data, reason: "%s"',
                $this->lastFailureReason
            );
        } else {
            $failureDescription = 'Failed assert that actual list of items match expected data';
        }

        throw new ExpectationFailedException(
            $failureDescription,
            $comparisonFailure
        );
    }

    private function assertListItemConstraints($listItemConstraints)
    {
        foreach ($listItemConstraints as $listItemConstraint) {
            $this->assertListItemConstraint($listItemConstraint);
        }
    }

    private function assertListItemConstraint($listItemConstraint)
    {
        $listItemConstraintClassName = static::getListItemConstraintClassName();
        if (!$listItemConstraint instanceof $listItemConstraintClassName) {
            throw new \LogicException(
                'List class name must be an instance of the '. AbstractMultipleAssertConstraint::class
            );
        }
    }

    abstract protected static function getListItemConstraintClassName(): string;
}
