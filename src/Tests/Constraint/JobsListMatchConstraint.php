<?php

namespace Klaviyo\Integration\Tests\Constraint;

class JobsListMatchConstraint extends AbstractListMatchConstraint
{
    public function toString(): string
    {
        return 'Is klaviyo historical event tracking jobs data match';
    }

    protected static function getListItemConstraintClassName(): string
    {
        return JobMatchConstraint::class;
    }
}
