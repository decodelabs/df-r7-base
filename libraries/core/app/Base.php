<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app;

use df;
use df\core;
use df\flex;

abstract class Base implements core\IApp
{
    const NAME = 'My application';
    const UNIQUE_PREFIX = '123';
    const PASS_KEY = 'temp-pass-key';
    const COMPOSER = true;

    const PACKAGES = [
        'webCore' => true
    ];

    public $startTime;
    public $path;

    public $envId;
    public $envMode = 'development';

    public $isDistributed = false;
    public $isMaintenance = false;

    public $runner;

    protected $_runMode;
    protected $_registry = [];

    public static function factory(string $envId, string $path): core\IApp
    {
        $class = 'df\\apex\\App';

        if (df\Launchpad::$isCompiled) {
            if (!class_exists($class)) {
                throw core\Error::EImplementation('App class not found');
            }
        } else {
            $filePath = $path.'/App.php';

            if (!file_exists($filePath)) {
                self::_generateClass($filePath);
            }

            require_once $filePath;
        }

        return new $class($envId, $path);
    }

    private static function _generateClass(string $path): void
    {
        $configPath = dirname($path).'/config/Application.php';

        if (file_exists($configPath)) {
            $appData = require $configPath;

            $name = $appData['applicationName'];
            $uniquePrefix = $appData['uniquePrefix'];
            $passKey = $appData['passKey'];
            $packages = $appData['packages'];
        } else {
            $name = 'My application';
            $uniquePrefix = strtolower(flex\Generator::random(3, 3));
            $passKey = flex\Generator::passKey();

            if (file_exists(dirname(df\Launchpad::DF_PATH).'/webCore/Package.php')) {
                $packages = ['webCore' => true];
            } else {
                $packages = [];
            }
        }

        $packageString = core\collection\Util::exportArray($packages);
        $packageString = str_replace("\n", "\n    ", $packageString);

        $class = <<<PHP
<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex;

use df;
use df\core;
use df\apex;

class App extends core\app\Base {

    const NAME = '{$name}';
    const UNIQUE_PREFIX = '{$uniquePrefix}';
    const PASS_KEY = '{$passKey}';

    const PACKAGES = {$packageString};
}
PHP;

        file_put_contents($path, $class);
    }

    public function __construct(string $envId, string $path)
    {
        $this->path = $path;

        $this->envId = $envId;
        $this->envMode = defined('df\\COMPILE_ENV_MODE') ?
            df\COMPILE_ENV_MODE : 'testing';

        // Set error handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }



    // Composer
    public function shouldUseComposer(): bool
    {
        return static::COMPOSER;
    }


    // Paths
    public function getPath(): string
    {
        return $this->path;
    }

    public function getLocalDataPath(): string
    {
        return $this->path.'/data/local';
    }

    public function getSharedDataPath(): string
    {
        return $this->path.'/data/shared';
    }


    // Environment
    public function getEnvId(): string
    {
        return $this->envId;
    }

    public function getEnvMode(): string
    {
        return $this->envMode;
    }

    public function isDevelopment(): bool
    {
        return $this->envMode == 'development';
    }

    public function isTesting(): bool
    {
        return $this->envMode == 'testing'
            || $this->envMode == 'development';
    }

    public function isProduction(): bool
    {
        return $this->envMode == 'production';
    }

    public function isDistributed(): bool
    {
        return $this->isDistributed;
    }


    public function getUniquePrefix(): string
    {
        return static::UNIQUE_PREFIX;
    }

    public function getPassKey(): string
    {
        return static::PASS_KEY;
    }



    // Details
    public function getName(): string
    {
        return static::NAME;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getRunningTime(): float
    {
        return microtime(true) - $this->startTime;
    }



    // Runner
    public function startup(float $startTime=null): void
    {
        if ($startTime === null) {
            $startTime = microtime(true);
        }

        $this->startTime = $startTime;


        // Env
        $envConfig = core\environment\Config::getInstance();
        $this->isDistributed = $envConfig->isDistributed();
        $this->isMaintenance = $envConfig->isMaintenanceMode();


        // Not compiled
        if (!df\Launchpad::$isCompiled) {
            $this->envMode = $envConfig->getMode();
            df\Launchpad::$loader->registerLocations($envConfig->getActiveLocations());
        }


        // Active packages
        $packages = [];

        foreach (static::PACKAGES ?? [] as $name => $enabled) {
            if (is_string($enabled)) {
                $name = $enabled;
                $enabled = true;
            }

            if ($enabled) {
                $packages[] = $name;
            }
        }

        df\Launchpad::$loader->loadPackages($packages);
    }


    public function run(): void
    {
        $runMode = $this->getRunMode();
        df\Launchpad::$runner = $this->runner = namespace\runner\Base::factory($runMode);
        $this->runner->dispatch();
    }

    public function shutdown(): void
    {
        foreach ($this->_registry as $object) {
            if ($object instanceof core\IShutdownAware) {
                $object->onAppShutdown();
            }
        }
    }

    public function getRunMode(): string
    {
        if ($this->_runMode === null) {
            $this->_runMode = $this->_detectRunMode();
        }

        return $this->_runMode;
    }

    protected function _detectRunMode(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $runMode = 'Http';
        } else {
            if (isset($_SERVER['argv'][1])) {
                $runMode = ucfirst($_SERVER['argv'][1]);
            } else {
                $runMode = 'Task';
            }
        }

        switch ($runMode) {
            case 'Http':
            case 'Daemon':
            case 'Task':
                break;

            default:
                $runMode = 'Task';
        }

        return $runMode;
    }



    // Registry
    public function setRegistryObject(core\IRegistryObject $object)
    {
        $this->_registry[$object->getRegistryObjectKey()] = $object;
        return $this;
    }

    public function getRegistryObject(string $key): ?core\IRegistryObject
    {
        if (isset($this->_registry[$key])) {
            return $this->_registry[$key];
        }

        return null;
    }

    public function hasRegistryObject(string $key): bool
    {
        return isset($this->_registry[$key]);
    }

    public function removeRegistryObject(string $key)
    {
        unset($this->_registry[$key]);
        return $this;
    }

    public function findRegistryObjects(string $beginningWith): array
    {
        $output = [];

        foreach ($this->_registry as $key => $object) {
            if (0 === strpos($key, $beginningWith)) {
                $output[$key] = $object;
            }
        }

        return $output;
    }

    public function getRegistryObjects(): array
    {
        return $this->_registry;
    }




    // Errors
    public static function handleError(int $errorNumber, string $errorMessage, string $fileName, int $lineNumber): void
    {
        if (!$level = error_reporting()) {
            return;
        }

        throw new \ErrorException($errorMessage, 0, $errorNumber, $fileName, $lineNumber);
    }

    public static function handleException(\Throwable $e): void
    {
        try {
            if (df\Launchpad::$runner) {
                try {
                    core\debug()
                        ->exception($e)
                        ->render();

                    df\Launchpad::shutdown();
                } catch (\Throwable $g) {
                    self::_fatalError($g->__toString()."\n\n\n".$e->__toString());
                }
            }

            self::_fatalError($e->__toString());
        } catch (\Throwable $f) {
            self::_fatalError($e->__toString()."\n\n\n".$f->__toString());
        }
    }

    private static function _fatalError($message): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $message = '<pre>'.$message.'</pre>';
        }

        echo $message;
        df\Launchpad::shutdown();
    }
}
