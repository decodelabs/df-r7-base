<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use df\core;
use df\core\app\Base as AppBase;
use df\Launchpad;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
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
            $appDir = Launchpad::$rootPath.'/tests';
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


    public function initializeLoaders(StackLoader $loader): void
    {
        Launchpad::initLoaders(
            $this->appPath,
            $this->context->getStartTime(),
            false
        );

        if ($this->analysis) {
            $this->initForAnalysis();
            return;
        }

        Launchpad::$app = AppBase::factory(
            $this->envId,
            $this->context->hub->getApplicationPath()
        );
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
                'root' => Launchpad::$isCompiled ?
                    Launchpad::$rootPath :
                    dirname(Launchpad::$rootPath)
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
