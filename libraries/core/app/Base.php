<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app;

use df;
use df\core;
use df\flex;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
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

    protected $_registry = [];
    protected $_csps = [];

    public static function factory(): core\IApp
    {
        $class = 'df\\apex\\App';

        if (Genesis::$build->isCompiled()) {
            if (!class_exists($class)) {
                throw Exceptional::Implementation(
                    'App class not found'
                );
            }
        } else {
            $filePath = Genesis::$hub->getApplicationPath().'/App.php';

            if (!file_exists($filePath)) {
                self::_generateClass($filePath);
            }

            require_once $filePath;
        }

        return new $class();
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



    public static function initializePlatform(): void
    {
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


    public function shutdown(): void
    {
        foreach ($this->_registry as $object) {
            if ($object instanceof core\IShutdownAware) {
                $object->onAppShutdown();
            }
        }
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
