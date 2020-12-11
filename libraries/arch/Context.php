<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;
use df\link;
use df\aura;

use df\arch\Scaffold;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Exceptional;

class Context implements IContext, \Serializable, Dumpable
{
    use core\TContext;
    use TResponseForcer;

    public $request;
    public $location;

    public static function getCurrent(): IContext
    {
        $runner = df\Launchpad::$runner;

        if ($runner instanceof core\IContextAware) {
            try {
                if ($context = $runner->getContext()) {
                    return $context;
                }
            } catch (core\app\runner\NoContextException $e) {
            }
        }

        return self::factory();
    }

    public static function getActive(): ?IContext
    {
        $runner = df\Launchpad::$runner;

        if ($runner instanceof core\IContextAware) {
            try {
                return $runner->getContext();
            } catch (core\app\runner\NoContextException $e) {
            }
        }

        return null;
    }

    public static function factory($location=null, $runMode=null, $request=null): IContext
    {
        $runner = df\Launchpad::$runner;

        if (!empty($location)) {
            $location = arch\Request::factory($location);
        } elseif ($runner instanceof core\IContextAware && $runner->hasContext()) {
            $location = $runner->getContext()->location;
        } else {
            $location = new arch\Request('/');
        }

        return new self($location, $runMode, $request);
    }

    public function __construct(arch\IRequest $location, $runMode=null, $request=null)
    {
        $this->runner = df\Launchpad::$runner;
        $this->location = $location;
        $runMode;

        if ($request === true) {
            $this->request = clone $location;
        } elseif ($request !== null) {
            $this->request = arch\Request::factory($request);
        } elseif ($this->runner instanceof core\IContextAware
               && $this->runner->hasContext()) {
            $this->request = $this->runner->getContext()->location;
        } else {
            $this->request = $location;
        }
    }

    public function __clone()
    {
        $this->request = clone $this->request;
        $this->location = clone $this->location;
    }

    public function spawnInstance($request=null, bool $copyRequest=false): IContext
    {
        if ($request === null) {
            return clone $this;
        }

        $request = arch\Request::factory($request);

        if ($request->eq($this->location)) {
            return $this;
        }

        $output = new self($request);

        if ($copyRequest) {
            $output->request = $output->location;
        }

        return $output;
    }


    public function serialize()
    {
        return (string)$this->location;
    }

    public function unserialize($data)
    {
        $this->location = Request::factory($data);
        $this->runner = df\Launchpad::$runner;

        if ($this->runner instanceof core\IContextAware
        && $this->runner->hasContext()) {
            $this->request = $this->runner->getContext()->location;
        } else {
            $this->request = $this->location;
        }

        return $this;
    }



    // Application
    public function getDispatchContext(): core\IContext
    {
        if (!$this->runner instanceof core\IContextAware) {
            throw Exceptional::NoContext(
                'Current runner is not context aware'
            );
        }

        return $this->runner->getContext();
    }

    public function isDispatchContext(): bool
    {
        return $this->getDispatchContext() === $this;
    }


    // Requests
    public function getRequest(): IRequest
    {
        return $this->request;
    }

    public function getLocation(): IRequest
    {
        return $this->location;
    }

    protected function _applyRequestRedirect(arch\IRequest $request, $from, $to)
    {
        if ($from !== null) {
            if ($from === true) {
                $from = $this->request;
            }

            $request->setRedirectFrom($from);
        }

        if ($to !== null) {
            if ($to === true) {
                $to = $this->request;
            }

            $request->setRedirectTo($to);
        }

        return $request;
    }


    public function extractDirectoryLocation(string &$path): IRequest
    {
        if (false !== strpos($path, '#')) {
            $parts = explode('#', $path, 2);
            $name = (string)array_pop($parts);
            $rem = (string)array_shift($parts);

            if (empty($rem)) {
                $parts = [];
            } else {
                $parts = explode('/', rtrim($rem, '/'));
            }
        } else {
            $parts = explode('/', $path);
            $name = (string)array_pop($parts);
        }

        if (empty($parts)) {
            $location = clone $this->location;
        } elseif ($parts[0] == '.' || $parts[0] == '..') {
            $parts[] = '';
            $location = $this->location->extractRelative($parts);
        } else {
            $location = new arch\Request(implode('/', $parts).'/');
        }

        $path = trim($name, '/');
        return $location;
    }

    public function extractThemeId(string &$path, bool $findDefault=false): ?string
    {
        $themeId = null;

        if (false !== strpos($path, '#')) {
            $parts = explode('#', $path, 2);
            $path = array_pop($parts);
            $themeId = trim((string)array_shift($parts), '/');

            if (empty($themeId)) {
                $themeId = null;
            }
        }

        if ($themeId === null && $findDefault) {
            $themeId = aura\theme\Config::getInstance()->getThemeIdFor($this->location->getArea());
        }

        return $themeId;
    }



    // Helpers
    protected function _loadHelper(string $name)
    {
        switch ($name) {
            case 'dispatchContext':
                return $this->getDispatchContext();

            case 'request':
                return $this->request;

            case 'location':
                return $this->location;

            case 'scaffold':
                return $this->getScaffold();

            default:
                return $this->loadRootHelper($name);
        }
    }

    public function getScaffold(): Scaffold
    {
        return arch\scaffold\Base::factory($this);
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            'request' => $this->request,
            'location' => $this->location
        ];
    }
}
