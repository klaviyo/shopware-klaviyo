<?php

namespace Klaviyo\Integration\Utils\Logger;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;

class ContextHelper
{
    private static ?string $pluginVersion = null;
    
    public const PLUGIN_VERSION_HEADER = 'X-Sw-Plugin-Version';
    
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

    public static function fetchPluginVersion(): string
    {
        if (self::$pluginVersion !== null) {
            return self::$pluginVersion;
        }

        self::$pluginVersion = 'unknown';

        if (class_exists(\Composer\InstalledVersions::class) && \Composer\InstalledVersions::isInstalled('klaviyo/shopware-klaviyo')) {
            self::$pluginVersion = \Composer\InstalledVersions::getPrettyVersion('klaviyo/shopware-klaviyo');
        } else {
            $composerFile = \dirname(__DIR__, 3) . '/composer.json';
            if (\is_readable($composerFile)) {
                $data = \json_decode(\file_get_contents($composerFile), true);
                if (\is_array($data) && isset($data['version']) && \is_string($data['version'])) {
                    self::$pluginVersion = $data['version'];
                }
            }
        }
        
        return self::$pluginVersion;
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