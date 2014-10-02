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
    
    const IN_PHAR = false;
    const IS_COMPILED = false;
    const COMPILE_TIMESTAMP = null;
    
    public static $applicationName;
    public static $applicationPath;
    
    public static $environmentId;
    public static $isTesting = true;
    
    public static $uniquePrefix;
    public static $passKey;
    public static $isDistributed = false;

    public static $loader;
    public static $application;
    public static $debug;
    
    private static $_startTime;
    private static $_isInit = false;
    private static $_isRun = false;
    private static $_isShutdown = false;
    
    public static function loadBaseClass($path) {
        if(self::IS_COMPILED) {
            $path = __DIR__.'/'.$path.'.php';
        } else {
            $path = __DIR__.'/libraries/'.$path.'.php';
        }

        require_once $path;
    }
    
// Run
    public static function run($appType=null) {
        $parts = explode('/', str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'])));
        $environmentId = array_pop($parts);

        if(substr($environmentId, -4) == '.php') {
            $environmentId = substr($environmentId, 0, -4);
        }
        
        $envParts = explode('.', $environmentId, 2);
        $environmentId = array_shift($envParts);
        $isTesting = true;
        
        if($environmentMode = array_shift($envParts)) {
            $isTesting = $environmentMode != 'production';
        }
        
        if(array_pop($parts) != 'entry') {
            throw new \Exception(
                'Entry point does not appear to be valid'
            );
        }
        
        return self::runAs($environmentId, $isTesting, implode('/', $parts), $appType);
    }
    
    public static function runAs($environmentId, $isTesting, $appPath, $appType=null) {
        if(self::$_isRun) {
            return;
        }
        
        self::init($environmentId, $isTesting, $appPath);
        self::$_isRun = true;
        
        // Set error handlers
        set_error_handler(['df\\Launchpad', 'handleError']);
        set_exception_handler(['df\\Launchpad', 'handleException']);
        
        if(!$appType) {
            if(isset($_SERVER['HTTP_HOST'])) {
                $appType = 'Http';
            } else {
                if(isset($_SERVER['argv'][1]) && $_SERVER['argv'][1]{0} != '-') {
                    $appType = ucfirst($_SERVER['argv'][1]);
                } else {
                    $appType = 'Cli';
                }
            }
        } else {
            $appType = ucfirst($appType);
        }
        
        if($appType == 'Http') {
            // If you're on apache, it sometimes hides some env variables = v. gay
            if(function_exists('apache_request_headers')) {
                foreach(apache_request_headers() as $key => $value) {
                    $_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
                }
            }

            if(isset($_SERVER['CONTENT_TYPE'])) {
                $_SERVER['HTTP_CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
            }
            
            // Normalize REQUEST_URI
            if(isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }
        }
        
        
        // Load application / packages
        self::$application = core\application\Base::factory($appType);

        $envConfig = core\Environment::getInstance();
        self::$isDistributed = $envConfig->isDistributed();

        if(!self::IS_COMPILED) {
            self::$loader->registerLocations($envConfig->getActiveLocations());
        }

        $appConfig = core\application\Config::getInstance();

        self::$applicationName = $appConfig->getApplicationName();
        self::$uniquePrefix = $appConfig->getUniquePrefix();
        self::$passKey = $appConfig->getPassKey();

        self::$loader->loadPackages($appConfig->getActivePackages());


        // Run
        $payload = self::$application->dispatch();


        // Write debug loggers
        if(self::$debug) {
            self::$debug->execute();
        }
        
        
        // Launch payload
        if($payload) {
            self::$application->launchPayload($payload);
        }
        
        //self::benchmark();
        self::shutdown();
    }
    
    public static function init($environmentId, $isTesting, $appPath) {
        if(self::$_isInit) {
            return;
        }
        
        self::$_isInit = true;
        self::$_startTime = microtime(true);
        self::$applicationPath = $appPath;
        self::$environmentId = $environmentId;
        self::$isTesting = (bool)$isTesting;

        // This probably needs putting on a leash!
        umask(0);
        
        // Very strict error reporting, makes sure you write clean code :)
        $errorReporting = E_ALL | E_STRICT;
        
        // Avoid setting if already done in php.ini
        if(error_reporting() != $errorReporting) {
            error_reporting($errorReporting);
        }
        
        // Set some global defaults
        date_default_timezone_set('UTC');
        mb_internal_encoding('UTF-8');
        
        // Make sure we're working from the entry point location
        chdir(self::$applicationPath.'/entry');
        
        // Load core library
        self::loadBaseClass('core/_manifest');


        // Register loader
        if(self::IS_COMPILED) {
            self::$loader = new core\Loader(['root' => dirname(self::DF_PATH)]);
        } else {
            self::$loader = new core\DevLoader(['root' => dirname(self::DF_PATH)]);
        }

        self::$loader->activate();
        self::$loader->loadBasePackages();
    }

    public static function getEnvironmentMode() {
        if(self::IS_COMPILED) {
            return self::$isTesting ? 'testing' : 'production';
        } else {
            return 'development';
        }
    }

    public static function isDevelopment() {
        return !self::IS_COMPILED;
    }
    
    public static function isTesting() {
        return self::$isTesting || !self::IS_COMPILED;
    }
    
    public static function isProduction() {
        return !self::$isTesting && self::IS_COMPILED;
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
    
    public static function handleException(\Exception $e) {
        try {
            if(self::$application) {
                try {
                    core\debug()
                        ->exception($e)
                        ->render();
                        
                    self::shutdown();
                } catch(\Exception $g) {}
            }
            
            self::_fatalError($e->__toString());
        } catch(\Exception $f) {
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
        if(self::IS_COMPILED) {
            return self::DF_PATH;
        } else {
            return self::DF_PATH.'/libraries';
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

            if(self::$application) {
                self::$debug = self::$application->createDebugContext();
            } else {
                self::$debug = new core\debug\Context();
            }
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
        return microtime(true) - self::$_startTime;
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