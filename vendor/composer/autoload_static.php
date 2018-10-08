<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfb499b858061dcbc3a5ecc8995cf88b8
{
    public static $files = array (
        '62c8af8f61fc138559e964ab4fef7ff3' => __DIR__ . '/..' . '/nextgenthemes/common/src/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'Vimeo\\' => 6,
        ),
        'N' => 
        array (
            'Nextgenthemes\\ARVE\\' => 19,
            'Nextgenthemes\\' => 14,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Vimeo\\' => 
        array (
            0 => __DIR__ . '/..' . '/vimeo/vimeo-api/src/Vimeo',
        ),
        'Nextgenthemes\\ARVE\\' => 
        array (
            0 => __DIR__ . '/../..' . '/public',
        ),
        'Nextgenthemes\\' => 
        array (
            0 => __DIR__ . '/..' . '/nextgenthemes/common/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfb499b858061dcbc3a5ecc8995cf88b8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfb499b858061dcbc3a5ecc8995cf88b8::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
