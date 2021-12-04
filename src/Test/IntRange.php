<?php declare(strict_types=1);

namespace Klaviyo\Integration\Test;

use Shopware\Core\Framework\Util\Random;

class IntRange
{
    private int $min;
    private int $max;

    public function __construct(
        int $min,
        int $max
    ) {
        if ($min > $max) {
            throw new \LogicException('\'min\' argument cannot be greater than \'max\' argument.');
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function getRandom(): int
    {
        return Random::getInteger($this->getMin(), $this->getMax());
    }

    public function getRandomUniqueValuesFromRange(): array
    {
        $numberOfItems = $this->getRandom();
        $randomUniqueValues = [];

        for ($i = $this->getMin(); $i <= $this->getMax(); $i++) {
            $randomUniqueValues[] = $i;
        }

        shuffle($randomUniqueValues);

        return array_slice($randomUniqueValues, 0, $numberOfItems);
    }
}
