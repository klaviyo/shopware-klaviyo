<?php

namespace Klaviyo\Integration\Utils\Logger;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Klaviyo\Integration\Utils\Reflection\ReflectionHelper;
use Shopware\Core\Kernel;
use Klaviyo\Integration\klavi_overd;
use Composer\InstalledVersions;

class ContextHelper
{
    private static ?array $pluginVersions = null;

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

    /**
     * Returns Klaviyo plugin versions from composer and database.
     *
     * @return array{composer_version: string, db_version: string}
     */
    public static function fetchPluginVersion(): array
    {
        if (self::$pluginVersions !== null) {
            return self::$pluginVersions;
        }

        $composerVersion = 'unknown';
        $dbVersion = 'unknown';

        try {
            if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('klaviyo/shopware-klaviyo')) {
                $composerVersion = (string) InstalledVersions::getPrettyVersion('klaviyo/shopware-klaviyo');
            }
        } catch (\Throwable $e) {
        }

        try {
            $connection = Kernel::getConnection();
            $baseClass = klavi_overd::class;
            /** @var string|null $version */
            $dbVersion = $connection->fetchOne('SELECT `version` FROM `plugin` WHERE `base_class` = :baseClass LIMIT 1', [
                'baseClass' => $baseClass,
            ]);
            if ($dbVersion === false || $dbVersion === null) {
                $dbVersion = 'unknown';
            }
        } catch (\Throwable $e) {
        }

        self::$pluginVersions = [
            'composer_version' => $composerVersion,
            'db_version' => $dbVersion,
        ];

        return self::$pluginVersions;
    }

    /**
     * Returns Shopware version string.
     */
    public static function fetchShopwareVersion(): string
    {
        $version = 'unknown';
        try {
            if (class_exists(InstalledVersions::class)) {
                if (InstalledVersions::isInstalled('shopware/core')) {
                    $version = (string) InstalledVersions::getPrettyVersion('shopware/core');
                }
            }
        } catch (\Throwable $e) {
        }

        return $version;
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
