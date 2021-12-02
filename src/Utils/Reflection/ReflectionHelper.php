<?php

namespace Klaviyo\Integration\Utils\Reflection;

class ReflectionHelper
{
    private static array $reflectionClass = [];

    public static function getObjectPropertiesValues(object $object): array
    {
        $reflectionClass = self::getReflectionClass($object);

        $result = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $property->getValue($object);
        }

        return $result;
    }

    private static function getReflectionClass($classOrObject): \ReflectionClass
    {
        $class = $classOrObject;
        if (is_object($classOrObject)) {
            $class = get_class($classOrObject);
        }

        if (!isset(self::$reflectionClass[$class])) {
            self::$reflectionClass[$class] = new \ReflectionClass($class);
        }

        return self::$reflectionClass[$class];
    }

    public static function isClassInstanceOf(string $className, string $expectedInstanceOfClass): bool
    {
        $reflectionClass = self::getReflectionClass($className);

        return $reflectionClass->isSubclassOf($expectedInstanceOfClass);
    }
}