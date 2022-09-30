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
use df\core\IApp as AppInterface;
use df\core\Config as ConfigBase;
use df\core\environment\Config as CoreEnvConfig;
use df\core\loader\Base as LoaderBase;
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
     * Get local data path
     */
    public function getLocalDataPath(): string
    {
        return $this->appPath.'/data/local';
    }

    /**
     * Get shared data path
     */
    public function getSharedDataPath(): string
    {
        return $this->appPath.'/data/shared';
    }


    /**
     * Get application name
     */
    public function getApplicationName(): string
    {
        static $name;

        if (!isset($name)) {
            $name = $this->context->container['app']::NAME;
        }

        return $name;
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
            /** @phpstan-ignore-next-line */
            df\COMPILE_ROOT_PATH !== null &&
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
    public function initializeLoaders(StackLoader $stack): void
    {
        // Load core library
        $this->loadBaseClass('core/_manifest');
        $this->loadBaseClass('core/loader/Base');

        $rootPath = $this->context->build->path;

        // Register loader
        if ($this->context->build->isCompiled()) {
            $loader = new core\loader\Base();
        } else {
            $this->loadBaseClass('core/loader/Development');

            $loader = new core\loader\Development();
        }

        $stack->registerLoader($loader);

        // Packages
        $loader->initRootPackages($rootPath, $this->appPath);


        // Set envId in config
        ConfigBase::$envId = $this->envId;



        // Load app

        /** @var AppBase $app */
        $app = AppBase::factory();


        // Add app and loader to Container
        $this->context->container->bindShared(AppBase::class, $app)
            ->alias('app');
        $this->context->container->bindShared(LoaderBase::class, $loader)
            ->alias('app.loader');


        // DELETE ME
        Launchpad::$app = $app;
        Launchpad::$loader = $loader;


        // Active packages
        $packages = [];

        foreach ($app::PACKAGES ?? [] as $name => $enabled) {
            if (is_string($enabled)) {
                $name = $enabled;
                $enabled = true;
            }

            if ($enabled) {
                $packages[] = $name;
            }
        }

        $loader->loadPackages($packages);


        if ($this->analysis) {
            Veneer::getDefaultManager()->setDeferrals(false);
        }
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



    /**
     * Load r7 env config
     */
    public function loadEnvironmentConfig(): EnvConfig
    {
        if ($this->analysis) {
            return new EnvConfig\Development($this->envId);
        }

        $conf = CoreEnvConfig::getInstance();

        /** @phpstan-ignore-next-line */
        $name = ucfirst(df\COMPILE_ENV_MODE ?? $conf->getMode());

        $class = EnvConfig::class.'\\'.$name;
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
            ->setRunMode($this->context->environment->getMode())
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
        return new Kernel($this->context, $this->detectKernel());
    }

    protected function detectKernel(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $kernel = 'Http';
        } elseif (isset($_SERVER['argv'])) {
            if (isset($_SERVER['argv'][1])) {
                $kernel = ucfirst($_SERVER['argv'][1]);
            } else {
                $kernel = 'Task';
            }
        } else {
            $kernel = null;
        }

        switch ($kernel) {
            case 'Http':
            case 'Daemon':
            case 'Task':
                return (string)$kernel;
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
}
