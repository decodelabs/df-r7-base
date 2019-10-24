<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df;

use df;
use df\core;

use DecodeLabs\Veneer;
use DecodeLabs\Glitch;

class Launchpad
{
    const CODENAME = 'hydrogen';
    const REV = 'r7';
    const DF_PATH = __DIR__;

    public static $isCompiled = false;
    public static $compileTimestamp = null;

    public static $rootPath = __DIR__;

    public static $loader;
    public static $app;
    public static $runner;

    private static $_isShutdown = false;



    // Run
    public static function run(): void
    {
        $parts = explode('/', str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'])));
        $envId = array_pop($parts);

        if (array_pop($parts) != 'entry') {
            throw new \Exception(
                'Entry point does not appear to be valid'
            );
        }

        if (substr($envId, -4) == '.php') {
            $envId = substr($envId, 0, -4);
        }

        $envParts = explode('.', $envId, 2);
        $envId = array_shift($envParts);

        self::runAs($envId, implode('/', $parts));
    }

    public static function runAs($envId, $appPath): void
    {
        if (self::$app) {
            return;
        }

        // Environment
        $startTime = self::initEnvironment();

        // Compilation
        self::initCompilation($appPath);

        // Loaders
        self::initLoaders($appPath, $startTime, true);

        // App
        self::$app = core\app\Base::factory($envId, $appPath);
        Glitch::setRunMode(self::$app->getEnvMode());

        // Run
        self::$app->startup($startTime);
        self::$app->run();

        self::shutdown();
    }

    public static function initEnvironment(): float
    {
        $startTime = microtime(true);

        // Set a few system defaults
        umask(0);
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        return $startTime;
    }

    public static function initCompilation(string $appPath): void
    {
        chdir($appPath.'/entry');

        // Check for compiled version
        $activePath = $appPath.'/data/local/run/active/Run.php';
        $sourceMode = isset($_SERVER['argv']) && in_array('--df-source', $_SERVER['argv']);

        if (file_exists($activePath) && !$sourceMode) {
            require $activePath;
        }

        if (!defined('df\\COMPILE_TIMESTAMP')) {
            define('df\\COMPILE_TIMESTAMP', null);
            define('df\\COMPILE_BUILD_ID', null);
            define('df\\COMPILE_ROOT_PATH', null);
            define('df\\COMPILE_ENV_NODE', null);
        }

        if (df\COMPILE_ROOT_PATH && is_dir(df\COMPILE_ROOT_PATH)) {
            self::$isCompiled = true;
            self::$compileTimestamp = df\COMPILE_TIMESTAMP;
            self::$rootPath = df\COMPILE_ROOT_PATH;
        }
    }

    public static function initLoaders(string $appPath, float $startTime=null, bool $loadComposer=false): void
    {
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


    public static function shutdown(): void
    {
        if (self::$_isShutdown) {
            return;
        }

        self::$_isShutdown = true;

        if (self::$app) {
            self::$app->shutdown();
        }

        if (self::$loader) {
            self::$loader->shutdown();
        }

        self::$app = null;
        self::$loader = null;

        exit;
    }


    // Loading
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
