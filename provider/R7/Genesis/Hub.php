<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use df;
use df\core;
use df\core\app\Base as AppBase;
use df\Launchpad;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Veneer;

class Hub implements HubInterface
{
    protected string $envId;
    protected string $appPath;

    protected bool $analysis = false;

    protected ?int $compileTimestamp = null;

    protected Context $context;

    public function __construct(
        Context $context,
        array $options
    ) {
        $this->context = $context;

        if ($options['analysis'] ?? false) {
            $this->prepareForAnalysis();
        } else {
            $this->parseEntry();
        }
    }


    private function prepareForAnalysis(): void
    {
        $this->analysis = true;
        $this->envId = 'analysis';

        $appDir = getcwd();
        $hasAppFile = file_exists($appDir.'/App.php');

        if (!$hasAppFile) {
            $appDir = dirname(dirname(dirname(__DIR__))).'/tests';
        }

        $this->appPath = $appDir;
    }


    /**
     * Work out envId and appPath from entry file
     */
    private function parseEntry(): void
    {
        $entryPath = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
        $parts = explode('/', $entryPath);
        $envId = array_pop($parts);

        if (array_pop($parts) != 'entry') {
            throw Exceptional::Setup(
                'Entry point does not appear to be valid'
            );
        }

        if (substr($envId, -4) == '.php') {
            $envId = substr($envId, 0, -4);
        }

        $envParts = explode('.', $envId, 2);

        $this->envId = array_shift($envParts);
        $this->appPath = implode('/', $parts);
    }


    /**
     * Get application path
     */
    public function getApplicationPath(): string
    {
        return $this->appPath;
    }



    /**
     * Load build info
     */
    public function loadBuild(): Build
    {
        // Ensure compile constants
        if (!defined('df\\COMPILE_TIMESTAMP')) {
            define('df\\COMPILE_TIMESTAMP', null);
            define('df\\COMPILE_BUILD_ID', null);
            define('df\\COMPILE_ROOT_PATH', null);
            define('df\\COMPILE_ENV_MODE', null);
        }


        // Work out root path
        if (
            df\COMPILE_ROOT_PATH &&
            is_dir((string)df\COMPILE_ROOT_PATH)
        ) {
            $buildPath = df\COMPILE_ROOT_PATH;
        } elseif ($this->analysis) {
            $buildPath = dirname(dirname(dirname(__DIR__)));
        } else {
            $buildPath = $this->appPath.'/vendor/df-r7/base';
        }



        // Create build info
        return new Build(
            $this->context,
            $buildPath,
            df\COMPILE_TIMESTAMP
        );
    }


    /**
     * Setup loaders
     */
    public function initializeLoaders(StackLoader $loader): void
    {
        // Load core library
        $this->loadBaseClass('core/_manifest');
        $this->loadBaseClass('core/loader/Base');

        $rootPath = $this->context->build->path;

        // Register loader
        if ($this->context->build->isCompiled()) {
            Launchpad::$loader = new core\loader\Base(['root' => dirname($rootPath)]);
        } else {
            $this->loadBaseClass('core/loader/Development');

            Launchpad::$loader = new core\loader\Development(['root' => dirname($rootPath)]);
        }

        // Packages
        Launchpad::$loader->initRootPackages($rootPath, $this->appPath);






        if ($this->analysis) {
            $this->initForAnalysis();
            return;
        }


        // Move this to Kernel once env config doesn't require $app
        Launchpad::$app = AppBase::factory(
            $this->envId,
            $this->context->hub->getApplicationPath()
        );
    }


    protected function loadBaseClass(string $path): void
    {
        if ($this->context->build->isCompiled()) {
            $path = $this->context->build->path.'/'.$path.'.php';
        } else {
            $path = $this->context->build->path.'/libraries/'.$path.'.php';
        }

        require_once $path;
    }



    protected function initForAnalysis(): void
    {
        Veneer::getDefaultManager()->setDeferrals(false);

        require_once $this->appPath.'/App.php';
        $appClass = 'df\\apex\\App';

        if (class_exists($appClass)) {
            Launchpad::$loader->loadPackages(array_keys($appClass::PACKAGES));
            $appClass::setupVeneerBindings();
        }
    }


    /**
     * Load r7 env config
     */
    public function loadEnvironmentConfig(): EnvConfig
    {
        if ($this->analysis) {
            return new EnvConfig\Development($this->envId);
        }

        $conf = core\environment\Config::getInstance();
        $class = EnvConfig::class.'\\'.ucfirst($conf->getMode());
        $output = new $class($this->envId);

        $output->setUmask(0);
        return $output;
    }

    /**
     * Setup Glitch
     */
    public function initializeErrorHandler(): void
    {
        Glitch::setStartTime($this->context->getstartTime())
            ->setRunMode($this->context->environment->getRunMode())
            ->registerPathAliases([
                'app' => $this->appPath,
                'vendor' => $this->appPath.'/vendor',
                'root' => $this->context->build->isCompiled() ?
                    $this->context->build->path :
                    dirname($this->context->build->path)
            ])
            ->registerAsErrorHandler()
            ->setLogListener(function ($exception) {
                core\logException($exception);
            });
    }


    /**
     * Load custom R7 kernel
     */
    public function loadKernel(): Kernel
    {
        return new Kernel($this->context);
    }
}
