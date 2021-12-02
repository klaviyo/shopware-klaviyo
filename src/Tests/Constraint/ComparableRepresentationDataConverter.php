<?php

namespace Klaviyo\Integration\Tests\Constraint;

class ComparableRepresentationDataConverter
{
    public const NULL_STRING_REPRESENTATION = 'null';

    public function convertDateTime(?\DateTimeInterface $dateTime): string
    {
        if ($dateTime === null) {
            return self::NULL_STRING_REPRESENTATION;
        }

        return $dateTime->format('c');
    }

    public function convertBool(?bool $value): string
    {
        if ($value === null) {
            return self::NULL_STRING_REPRESENTATION;
        }

        return $value ? 'True' : 'False';
    }

    public function convertString(?string $status): string
    {
        return $status === null ? self::NULL_STRING_REPRESENTATION : $status;
    }

    public function convertInt(?int $value): string
    {
        return $value === null ? self::NULL_STRING_REPRESENTATION : (string)$value;
    }
}
