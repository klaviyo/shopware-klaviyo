<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitef5fa6064a03e2a4506f51520ff0b1c5
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Od\\Scheduler\\' => 13,
        ),
        'K' => 
        array (
            'Klaviyo\\Integration\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Od\\Scheduler\\' => 
        array (
            0 => __DIR__ . '/..' . '/od/sw6-job-scheduler/src',
        ),
        'Klaviyo\\Integration\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitef5fa6064a03e2a4506f51520ff0b1c5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitef5fa6064a03e2a4506f51520ff0b1c5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitef5fa6064a03e2a4506f51520ff0b1c5::$classMap;

        }, null, ClassLoader::class);
    }
}
