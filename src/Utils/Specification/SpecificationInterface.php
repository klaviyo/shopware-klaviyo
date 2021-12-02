<?php

namespace Klaviyo\Integration\Utils\Specification;

interface SpecificationInterface
{
    /**
     * @param mixed $value
     *
     * @return bool
     * @throws \Throwable
     */
    public function isSatisfiedBy($value): bool;
}
