<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use DecodeLabs\Archetype;
use DecodeLabs\Archetype\Resolver\Extension as ArchetypeExtension;
use DecodeLabs\Atlas;
use DecodeLabs\Disciple;
use DecodeLabs\Dovetail;
use DecodeLabs\Exceptional;
use DecodeLabs\Fluidity\CastTrait;
use DecodeLabs\Genesis\Build;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Environment\Config as EnvConfig;
use DecodeLabs\Genesis\Hub as HubInterface;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\Genesis\Loader\Stack as StackLoader;
use DecodeLabs\Glitch;
use DecodeLabs\Integra\Context as IntegraContext;
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Config\Environment as EnvironmentConfig;
use DecodeLabs\R7\Disciple\Adapter as DiscipleAdapter;
use DecodeLabs\R7\Dovetail\Resolver as DovetailResolver;
use DecodeLabs\R7\Genesis\Kernel as R7Kernel;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;

use df;
use df\core;
use df\core\app\Base as AppBase;
use df\core\loader\Base as LoaderBase;

use Throwable;

class Hub implements HubInterface
{
    use CastTrait;

    protected string $envId;
    protected string $appPath;

    protected bool $analysis = false;

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

        if (!$appDir = getcwd()) {
            throw Exceptional::Runtime('Unable to get current working directory');
        }

        $hasAppFile = file_exists($appDir . '/App.php');

        if (!$hasAppFile) {
            $appDir = dirname(dirname(__DIR__)) . '/tests';
        }

        $this->appPath = $appDir;
    }


    /**
     * Work out envId and appPath from entry file
     */
    private function parseEntry(): void
    {
        $entryPath = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME']));
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

        $this->envId = (string)array_shift($envParts);
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
        return $this->appPath . '/data/local';
    }

    /**
     * Get shared data path
     */
    public function getSharedDataPath(): string
    {
        return $this->appPath . '/data/shared';
    }


    /**
     * Get application name
     */
    public function getApplicationName(): string
    {
        static $name;

        if (!isset($name)) {
            $name = Legacy::app()::NAME;
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
            $buildPath = dirname(dirname(__DIR__));
        } else {
            $buildPath = $this->appPath . '/vendor/df-r7/base';
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


        // Load app
        /** @var AppBase $app */
        $app = AppBase::factory();


        // Add app and loader to Container
        $this->context->container->bindShared(AppBase::class, $app)
            ->alias('app');
        $this->context->container->bindShared(LoaderBase::class, $loader)
            ->alias('app.loader');


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


        // Veneer bindings
        Veneer::register(Legacy\Helper::class, Legacy::class);

        // Dovetail
        Dovetail::setEnvPath($this->appPath);
        Archetype::register(new DovetailResolver());

        /*
        if($this->context->build->isCompiled()) {
            Dovetail::setEnvPath($this->context->build->path.'/apex');

            Dovetail::setFinder(new DovetailFinder(
                $this->context->build->path.'/apex'
            ));
        }
        */
    }


    protected function loadBaseClass(string $path): void
    {
        if ($this->context->build->isCompiled()) {
            $path = $this->context->build->path . '/' . $path . '.php';
        } else {
            $path = $this->context->build->path . '/libraries/' . $path . '.php';
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

        EnvironmentConfig::setEnvId($this->envId);
        EnvironmentConfig::setAppPath($this->appPath);

        /** @phpstan-ignore-next-line */
        $name = ucfirst(df\COMPILE_ENV_MODE ?? EnvironmentConfig::load()->getMode());

        /** @var class-string<EnvConfig\Development|EnvConfig\Testing|EnvConfig\Production> */
        $class = EnvConfig::class . '\\' . $name;
        $output = new $class($this->envId);

        $output->setUmask(0);

        return $output;
    }

    /**
     * Initialize platform
     */
    public function initializePlatform(): void
    {
        // Setup Glitch
        Glitch::setStartTime($this->context->getstartTime())
            ->setRunMode($this->context->environment->getMode())
            ->registerPathAliases([
                'app' => $this->appPath,
                'vendor' => $this->appPath . '/vendor',
                'root' => $this->context->build->isCompiled() ?
                    $this->context->build->path :
                    dirname($this->context->build->path)
            ])
            ->registerAsErrorHandler()
            ->setLogListener(function ($exception) {
                core\logException($exception);
            });


        // Archetype registration
        Archetype::register(new ArchetypeExtension(
            Kernel::class,
            R7Kernel::class /** @phpstan-ignore-line */
        ));


        // Set Disciple adapter
        Disciple::setAdapter(new DiscipleAdapter());


        // Set Metamorph URL resolver
        Metamorph::setUrlResolver(function (string $url): string {
            try {
                return (string)Legacy::uri($url);
            } catch (Throwable $e) {
                return $url;
            }
        });

        $this->context->container->bind(
            IntegraContext::class,
            new IntegraContext(Atlas::dir($this->getApplicationPath()))
        );

        $this->context->container->get(AppBase::class)->initializePlatform();
    }


    /**
     * Load custom R7 kernel
     */
    public function loadKernel(): Kernel
    {
        $class = Archetype::resolve(Kernel::class, $this->detectKernel());
        return new $class($this->context);
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
            'Unable to detect run mode (' . \PHP_SAPI . ')'
        );
    }


    public function getBuildManifest(): ?BuildManifest
    {
        return new BuildManifest(Cli::getSession());
    }
}
