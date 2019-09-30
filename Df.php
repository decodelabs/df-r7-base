<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df;

use df;

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
    public static $debug;

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

        $startTime = microtime(true);

        // Set a few system defaults
        umask(0);
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');
        chdir($appPath.'/entry');

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

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


        // Load core library
        self::loadBaseClass('core/_manifest');

        // Register loader
        if (self::$isCompiled) {
            self::$loader = new core\loader\Base(['root' => dirname(self::$rootPath)]);
        } else {
            self::$loader = new core\loader\Development(['root' => dirname(self::$rootPath)]);
        }

        self::$loader->initRootPackages(self::$rootPath, $appPath);


        // App
        self::$app = core\app\Base::factory($envId, $appPath);

        // Composer
        if (method_exists(self::$app, 'shouldIncludeComposer')) {
            $composer = self::$app->shouldIncludeComposer();

            if (!$composer && !class_exists('\\Composer\\Autoload\\ClassLoader')) {
                $composer = true;
            }
        } else {
            $composer = true;
        }

        if ($composer && class_exists('\\Composer\\Autoload\\ClassLoader')) {
            $composer = false;
        }

        if ($composer) {
            $path = null;

            if (self::$isCompiled) {
                $path = self::$rootPath.'/apex/vendor/autoload.php';

                if (!file_exists($path)) {
                    $path = null;
                }
            }

            if ($path === null) {
                $path = self::$app->getPath().'/vendor/autoload.php';

                if (!file_exists($path)) {
                    $path = null;
                }
            }

            if ($path !== null) {
                require_once $path;
            }
        }

        // Run
        self::$app->startup($startTime);
        self::$app->run();

        self::shutdown();
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


    // Debug
    public static function setDebugContext(core\debug\IContext $context=null): ?core\debug\IContext
    {
        $output = self::$debug;
        self::$debug = $context;

        return $output;
    }

    public static function getDebugContext(): core\debug\IContext
    {
        if (!self::$debug) {
            self::loadBaseClass('core/debug/Context');
            self::$debug = new core\debug\Context();
        }

        return self::$debug;
    }
}
