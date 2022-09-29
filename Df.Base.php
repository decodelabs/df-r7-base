<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df;

use df;
use df\core;

abstract class LaunchpadBase
{
    public const CODENAME = 'hydrogen';
    public const REV = 'r7';
    public const DF_PATH = __DIR__;

    public static bool $isCompiled = false;
    public static ?int $compileTimestamp = null;

    public static string $rootPath = __DIR__;

    public static $loader;
    public static $app;
    public static $runner;

    private static bool $_isShutdown = false;

    abstract public static function run(): void;
    abstract public static function initEnvironment(): float;

    public static function initLoaders(
        string $appPath,
        float $startTime=null,
        bool $loadComposer=false
    ): void {
        self::ensureCompileConstants();

        // Ensure root has not been mangled by symlink
        if (self::$rootPath === __DIR__) {
            $dir = $appPath.'/vendor/df-r7/base';

            if (self::$rootPath !== $dir && is_dir($dir)) {
                self::$rootPath = $dir;
            }
        }

        // Load core library
        self::loadBaseClass('core/_manifest');

        // Register loader
        if (self::$isCompiled) {
            self::$loader = new core\loader\Base(['root' => dirname(self::$rootPath)]);
        } else {
            self::$loader = new core\loader\Development(['root' => dirname(self::$rootPath)]);
        }

        // Composer
        if ($loadComposer) {
            self::$loader->loadComposer($appPath);
        }

        // Packages
        self::$loader->initRootPackages(self::$rootPath, $appPath);

        // Env setup
        if (class_exists(core\app\EnvSetup::class)) {
            core\app\EnvSetup::setup($appPath, $startTime);
        }
    }

    protected static function ensureCompileConstants()
    {
        if (!defined('df\\COMPILE_TIMESTAMP')) {
            define('df\\COMPILE_TIMESTAMP', null);
            define('df\\COMPILE_BUILD_ID', null);
            define('df\\COMPILE_ROOT_PATH', null);
            define('df\\COMPILE_ENV_MODE', null);
        }

        if (
            df\COMPILE_ROOT_PATH &&
            is_dir(df\COMPILE_ROOT_PATH)
        ) {
            static::$isCompiled = true;
            static::$compileTimestamp = df\COMPILE_TIMESTAMP;
            static::$rootPath = df\COMPILE_ROOT_PATH;
        }
    }

    public static function shutdown(): void
    {
        if (static::$_isShutdown) {
            return;
        }

        static::$_isShutdown = true;

        if (static::$app) {
            static::$app->shutdown();
        }

        if (static::$loader) {
            static::$loader->shutdown();
        }

        static::$app = null;
        static::$loader = null;

        exit;
    }


    public static function loadBaseClass($path): void
    {
        if (self::$isCompiled) {
            $path = self::$rootPath.'/'.$path.'.php';
        } else {
            $path = self::$rootPath.'/libraries/'.$path.'.php';
        }

        require_once $path;
    }
}
