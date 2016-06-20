<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df;

use df;

class Launchpad {

    const CODENAME = 'hydrogen';
    const REV = 'r7';
    const DF_PATH = __DIR__;

    public static $isCompiled = false;
    public static $compileTimestamp = null;

    public static $rootPath = __DIR__;

    public static $applicationName;
    public static $applicationPath;

    public static $environmentId;
    public static $environmentMode = 'testing';

    public static $uniquePrefix;
    public static $passKey;
    public static $isDistributed = false;

    public static $loader;
    public static $application;
    public static $debug;

    public static $startTime;

    private static $_isShutdown = false;

    public static function loadBaseClass($path) {
        if(self::$isCompiled) {
            $path = self::$rootPath.'/'.$path.'.php';
        } else {
            $path = self::$rootPath.'/libraries/'.$path.'.php';
        }

        require_once $path;
    }

// Run
    public static function run() {
        $parts = explode('/', str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'])));
        $environmentId = array_pop($parts);

        if(array_pop($parts) != 'entry') {
            throw new \Exception(
                'Entry point does not appear to be valid'
            );
        }

        if(substr($environmentId, -4) == '.php') {
            $environmentId = substr($environmentId, 0, -4);
        }

        $envParts = explode('.', $environmentId, 2);
        $environmentId = array_shift($envParts);

        return self::runAs($environmentId, implode('/', $parts));
    }

    public static function runAs($environmentId, $appPath) {
        if(self::$startTime) {
            return;
        }

        self::$startTime = microtime(true);
        self::$applicationPath = $appPath;
        self::$environmentId = $environmentId;

        // Set a few system defaults
        umask(0);
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');
        mb_internal_encoding('UTF-8');
        chdir(self::$applicationPath.'/entry');

        // Check for compiled version
        $activePath = $appPath.'/data/local/run/Active.php';

        if(file_exists($activePath) && (!$_SERVER['argc'] || !in_array('--df-source', $_SERVER['argv']))) {
            require $activePath;
        }

        // Load core library
        self::loadBaseClass('core/_manifest');

        // Register loader
        if(self::$isCompiled) {
            self::$loader = new core\loader\Base(['root' => dirname(self::$rootPath)]);
        } else {
            self::$loader = new core\loader\Development(['root' => dirname(self::$rootPath)]);
        }

        // Set error handlers
        set_error_handler(['df\\Launchpad', 'handleError']);
        set_exception_handler(['df\\Launchpad', 'handleException']);

        if(isset($_SERVER['HTTP_HOST'])) {
            $appType = 'Http';
        } else {
            if(isset($_SERVER['argv'][1])) {
                $appType = ucfirst($_SERVER['argv'][1]);
            } else {
                $appType = 'Task';
            }
        }

        switch($appType) {
            case 'Http':
            case 'Daemon':
            case 'Task':
                break;

            default:
                $appType = 'Task';
        }



        // Load application / packages
        $envConfig = core\Environment::getInstance();
        self::$isDistributed = $envConfig->isDistributed();

        if(!self::$isCompiled) {
            self::$environmentMode = $envConfig->getMode();
        }



        $class = 'df\\core\\application\\'.$appType;
        self::$application = new $class();

        if(!self::$isCompiled) {
            self::$loader->registerLocations($envConfig->getActiveLocations());
        }

        $appConfig = core\application\Config::getInstance();

        self::$applicationName = $appConfig->getApplicationName();
        self::$uniquePrefix = $appConfig->getUniquePrefix();
        self::$passKey = $appConfig->getPassKey();

        self::$loader->loadPackages($appConfig->getActivePackages());

        self::$application->dispatch();
        self::shutdown();
    }

    public static function getEnvironmentId() {
        return self::$environmentId;
    }

    public static function getEnvironmentMode() {
        return self::$environmentMode;
    }

    public static function isDevelopment(): bool {
        return self::$environmentMode == 'development';
    }

    public static function isTesting(): bool {
        return self::$environmentMode == 'testing'
            || self::$environmentMode == 'development';
    }

    public static function isProduction(): bool {
        return self::$environmentMode == 'production';
    }


    public static function shutdown() {
        if(self::$_isShutdown) {
            return;
        }

        self::$_isShutdown = true;

        if(self::$application) {
            self::$application->shutdown();
            self::$application = null;
        }

        if(self::$loader) {
            self::$loader->shutdown();
            self::$loader = null;
        }

        exit;
    }



// Errors
    public static function handleError($errorNumber, $errorMessage, $fileName, $lineNumber) {
        if(!$level = error_reporting()) {
            return;
        }

        throw new \ErrorException($errorMessage, 0, $errorNumber, $fileName, $lineNumber);
    }

    public static function handleException(\Throwable $e) {
        try {
            if(self::$application) {
                try {
                    core\debug()
                        ->exception($e)
                        ->render();

                    self::shutdown();
                } catch(\Throwable $g) {}
            }

            self::_fatalError($e->__toString());
        } catch(\Throwable $f) {
            self::_fatalError($e->__toString()."\n\n\n".$f->__toString());
        }
    }

    private static function _fatalError($message) {
        while(ob_get_level()) {
            ob_end_clean();
        }

        if(isset($_SERVER['HTTP_HOST'])) {
            $message = '<pre>'.$message.'</pre>';
        }

        echo $message;
        self::shutdown();
    }


// Application
    public static function getApplication() {
        if(!self::$application) {
            $class = 'df\\core\\application\\LogicException';

            if(!class_exists($class)) {
                $class = '\\LogicException';
            }

            throw new $class(
                'No active application is available'
            );
        }

        return self::$application;
    }


// Paths
    public static function getApplicationPath() {
        return self::$applicationPath;
    }

    public static function getBasePackagePath() {
        if(self::$isCompiled) {
            return self::$rootPath;
        } else {
            return self::$rootPath.'/libraries';
        }
    }


// Debug
    public static function setDebugContext(core\debug\IContext $context=null) {
        $output = self::$debug;
        self::$debug = $context;

        return $output;
    }

    public static function getDebugContext() {
        if(!self::$debug) {
            self::loadBaseClass('core/debug/Context');
            self::$debug = new core\debug\Context();
        }

        return self::$debug;
    }

// Benchmark
    public static function benchmark() {
        echo '<pre>';
        echo "\n\n".self::getFormattedRunningTime()."\n";

        $includes = get_included_files();
        echo count($includes).' files included'."\n\n";
        echo implode("\n", $includes);
        echo '</pre>'."\n\n";

        self::shutdown();
    }

    public static function getRunningTime() {
        return microtime(true) - self::$startTime;
    }

    public static function getFormattedRunningTime($seconds=null) {
        if($seconds === null) {
            $seconds = self::getRunningTime();
        }

        if($seconds > 60) {
            return number_format($seconds / 60, 0).':'.number_format($seconds % 60);
        } else if($seconds > 1) {
            return number_format($seconds, 3).' s';
        } else if($seconds > 0.0005) {
            return number_format($seconds * 1000, 3).' ms';
        } else {
            return number_format($seconds * 1000, 5).' ms';
        }
    }
}