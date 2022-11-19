<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;

use df\arch;

use df\arch\scaffold\Loader as ScaffoldLoader;
use df\core;

class Context implements IContext, \Serializable, Dumpable
{
    use core\TContext;
    use TResponseForcer;

    public $request;
    public $location;

    public static function factory($location = null, $request = null): IContext
    {
        if (!empty($location)) {
            $location = arch\Request::factory($location);
        } elseif ($currentLocation = Legacy::getActiveContext()?->location) {
            $location = $currentLocation;
        } else {
            $location = new arch\Request('/');
        }

        return new self($location, $request);
    }

    public function __construct(arch\IRequest $location, $request = null)
    {
        $this->location = $location;

        if ($request === true) {
            $this->request = clone $location;
        } elseif ($request !== null) {
            $this->request = arch\Request::factory($request);
        } elseif ($currentLocation = Legacy::getActiveContext()?->location) {
            $this->request = $currentLocation;
        } else {
            $this->request = $location;
        }
    }

    public function __clone()
    {
        $this->request = clone $this->request;
        $this->location = clone $this->location;
    }

    public function spawnInstance($request = null, bool $copyRequest = false): IContext
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

    public function unserialize(string $data): void
    {
        $this->location = Request::factory($data);

        if ($currentLocation = Legacy::getActiveContext()?->location) {
            $this->request = $currentLocation;
        } else {
            $this->request = $this->location;
        }
    }

    public function __serialize(): array
    {
        return [
            (string)$this->location
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->unserialize($data[0]);
    }



    // Application
    public function getDispatchContext(): core\IContext
    {
        return Legacy::getContext();
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
            $location = new arch\Request(implode('/', $parts) . '/');
        }

        $path = trim($name, '/');
        return $location;
    }

    public function extractThemeId(string &$path, bool $findDefault = false): ?string
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
            $themeId = Legacy::getThemeIdFor($this->location->getArea());
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
        return ScaffoldLoader::fromContext($this);
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
