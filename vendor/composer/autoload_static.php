<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8c016acfb63e012748cec901ae5dd2c4
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'App\\Auth' => __DIR__ . '/../..' . '/src/Auth.php',
        'App\\EmailService' => __DIR__ . '/../..' . '/src/EmailService.php',
        'App\\Models\\ActivityLog' => __DIR__ . '/../..' . '/src/Models/ActivityLog.php',
        'App\\Models\\Client' => __DIR__ . '/../..' . '/src/Models/Client.php',
        'App\\Models\\Notification' => __DIR__ . '/../..' . '/src/Models/Notification.php',
        'App\\Models\\Setting' => __DIR__ . '/../..' . '/src/Models/Setting.php',
        'App\\Models\\ShipmentOrder' => __DIR__ . '/../..' . '/src/Models/ShipmentOrder.php',
        'App\\Models\\ShipmentTracking' => __DIR__ . '/../..' . '/src/Models/ShipmentTracking.php',
        'App\\Models\\User' => __DIR__ . '/../..' . '/src/Models/User.php',
        'App\\TelegramService' => __DIR__ . '/../..' . '/src/TelegramService.php',
        'App\\VerificationService' => __DIR__ . '/../..' . '/src/VerificationService.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8c016acfb63e012748cec901ae5dd2c4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8c016acfb63e012748cec901ae5dd2c4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8c016acfb63e012748cec901ae5dd2c4::$classMap;

        }, null, ClassLoader::class);
    }
}
