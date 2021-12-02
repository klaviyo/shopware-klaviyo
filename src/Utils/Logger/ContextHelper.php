<?php

namespace Klaviyo\Integration\Utils\Logger;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;

class ContextHelper
{
    public static function createContextFromException(\Throwable $exception): array
    {
        $context = ['exception' => $exception];
        if ($exception instanceof LoggableContextAwareExceptionInterface) {
            $exceptionContext = self::convertLoggableContextToPlainRepresentation($exception->getLoggableContext());

            $context['exceptionContext'] = $exceptionContext;
        }

        return $context;
    }

    public static function convertLoggableContextToPlainRepresentation(array $context): array
    {
        try {
            foreach ($context as &$contextItem) {
                $contextItem = self::convertVariableToSerializableRepresentation($contextItem);
            }

            return $context;
        } catch (\Throwable $throwable) {
            $context['Exception during context conversion'] = $throwable->getMessage();

            return $context;
        }
    }

    /**
     * Convert objects into plain array
     *
     * @param int|float|string|array|object $value
     * @param int $deep How deep variable should be converted, by default 10 levels
     *
     * @return int|float|string|array
     */
    public static function convertVariableToSerializableRepresentation($value, int $deep = 10)
    {
        if ($deep < 1) {
            return '{maximum nesting level reached}';
        }
        $deep--;

        if ($value instanceof Request) {
            return self::convertRequestToSerializable($value);
        }
        if ($value instanceof Response) {
            return self::convertResponseToSerializable($value);
        }

        if ($value instanceof \Generator) {
            return '{Generator}';
        }
        if (is_iterable($value)) {
            $converted = [];

            foreach ($value as $row) {
                $converted[] = self::convertVariableToSerializableRepresentation($row, $deep);
            }

            return $converted;
        }

        if (is_object($value)) {
            $objectProperties = ReflectionHelper::getObjectPropertiesValues($value);
            self::convertVariableToSerializableRepresentation($objectProperties, $deep);
        }

        return $value;
    }

    private static function convertRequestToSerializable(Request $request): array
    {
        // @todo: remove token

        return [];
    }

    private static function convertResponseToSerializable(Response $response): array
    {

        return [];
    }
}