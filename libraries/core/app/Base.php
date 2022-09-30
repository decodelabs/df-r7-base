<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app;

use df;
use df\core;
use df\flex;
use df\link;

use df\user\Disciple\Adapter as DiscipleAdapter;

use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Sanctum\Definition as Csp;
use DecodeLabs\Veneer;

abstract class Base implements core\IApp
{
    public const NAME = 'My application';
    public const UNIQUE_PREFIX = '123';
    public const PASS_KEY = 'temp-pass-key';

    public const PACKAGES = [
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

    protected $_csps = [];

    public static function factory(string $envId, string $path): core\IApp
    {
        $class = 'df\\apex\\App';

        if (Genesis::$build->isCompiled()) {
            if (!class_exists($class)) {
                throw Exceptional::Implementation(
                    'App class not found'
                );
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

            if (file_exists(dirname(Genesis::$build->path).'/webCore/Package.php')) {
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
        /** @phpstan-ignore-next-line */
        $this->envMode = df\COMPILE_ENV_MODE ?? 'testing';
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
        if (!Genesis::$build->isCompiled()) {
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

        $this->setup();
    }

    public static function setup(): void
    {
        static::setup3rdParty();
        static::setupVeneerBindings();
    }

    public static function setup3rdParty(): void
    {
        Disciple::setAdapter(new DiscipleAdapter());

        Metamorph::setUrlResolver(function (string $url): string {
            try {
                return (string)Legacy::uri($url);
            } catch (\Throwable $e) {
                return $url;
            }
        });
    }

    public static function setupVeneerBindings(): void
    {
        Veneer::register(Legacy\Helper::class, Legacy::class);
    }


    final public function getCsp(string $contentType): ?Csp
    {
        $contentType = trim(strtolower($contentType));

        if (!isset($this->_csps[$contentType])) {
            $this->_csps[$contentType] = $csp = static::loadCsp($contentType) ?? false;

            if (
                $csp &&
                $csp->getReportUri() === null
            ) {
                $csp->setReportUri(Legacy::uri('pest-control/csp-report'));
            }
        }

        return $this->_csps[$contentType] ?
            $this->_csps[$contentType] : null;
    }

    protected static function loadCsp(string $contentType): ?Csp
    {
        return null;
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
        } elseif (isset($_SERVER['argv'])) {
            if (isset($_SERVER['argv'][1])) {
                $runMode = ucfirst($_SERVER['argv'][1]);
            } else {
                $runMode = 'Task';
            }
        } else {
            $runMode = null;
        }

        switch ($runMode) {
            case 'Http':
            case 'Daemon':
            case 'Task':
                return (string)$runMode;
        }

        switch (\PHP_SAPI) {
            case 'cli':
            case 'phpdbg':
                return 'Task';

            case 'apache':
            case 'apache2filter':
            case 'apache2handler':
            case 'fpm-fcgi':
            case 'cgi-fcgi':
            case 'phttpd':
            case 'pi3web':
            case 'thttpd':
                return 'Http';
        }

        throw Exceptional::UnexpectedValue(
            'Unable to detect run mode ('.\PHP_SAPI.')'
        );
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
}
