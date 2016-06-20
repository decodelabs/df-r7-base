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

class Context implements IContext, \Serializable, core\IDumpable {

    use core\TContext;
    use TResponseForcer;

    public $request;
    public $location;

    public static function getCurrent($onlyActive=false) {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\IContextAware) {
            return $application->getContext();
        }

        if($onlyActive) {
            return null;
        }

        return self::factory();
    }

    public static function factory($location=null, $runMode=null, $request=null) {
        $application = df\Launchpad::getApplication();

        if(!empty($location)) {
            $location = arch\Request::factory($location);
        } else if($application instanceof core\IContextAware && $application->hasContext()) {
            $location = $application->getContext()->location;
        } else {
            $location = new arch\Request('/');
        }

        return new self($location, $runMode, $request);
    }

    public function __construct(arch\IRequest $location, $runMode=null, $request=null) {
        $this->application = df\Launchpad::$application;
        $this->location = $location;

        if($request === true) {
            $this->request = clone $location;
        } else if($request !== null) {
            $this->request = arch\Request::factory($request);
        } else if($this->application instanceof core\IContextAware
               && $this->application->hasContext()) {
            $this->request = $this->application->getContext()->location;
        } else {
            $this->request = $location;
        }
    }

    public function __clone() {
        $this->request = clone $this->request;
        $this->location = clone $this->location;
        return $this;
    }

    public function spawnInstance($request=null, $copyRequest=false) {
        if($request === null) {
            return clone $this;
        }

        $request = arch\Request::factory($request);

        if($request->eq($this->location)) {
            return $this;
        }

        $output = new self($request);

        if($copyRequest) {
            $output->request = $output->location;
        }

        return $output;
    }


    public function serialize() {
        return (string)$this->location;
    }

    public function unserialize($data) {
        $this->location = Request::factory($data);
        $this->application = df\Launchpad::$application;

        if($this->application instanceof core\IContextAware
        && $this->application->hasContext()) {
            $this->request = $this->application->getContext()->location;
        } else {
            $this->request = $this->location;
        }

        return $this;
    }



// Application
    public function getDispatchContext() {
        if(!$this->application instanceof core\IContextAware) {
            throw new RuntimeException(
                'Current application is not context aware'
            );
        }

        return $this->application->getContext();
    }

    public function isDispatchContext(): bool {
        return $this->getDispatchContext() === $this;
    }


// Requests
    public function getRequest() {
        return $this->request;
    }

    public function getLocation() {
        return $this->location;
    }

    protected function _applyRequestRedirect(arch\IRequest $request, $from, $to) {
        if($from !== null) {
            if($from === true) {
                $from = $this->request;
            }

            $request->setRedirectFrom($from);
        }

        if($to !== null) {
            if($to === true) {
                $to = $this->request;
            }

            $request->setRedirectTo($to);
        }

        return $request;
    }


    public function extractDirectoryLocation(&$path) {
        if(false !== strpos($path, '#')) {
            $parts = explode('#', $path, 2);
            $name = array_pop($parts);
            $rem = array_shift($parts);

            if(empty($rem)) {
                $parts = [];
            } else {
                $parts = explode('/', rtrim($rem, '/'));
            }
        } else {
            $parts = explode('/', $path);
            $name = array_pop($parts);
        }

        if(empty($parts)) {
            $location = clone $this->location;
        } else if($parts[0] == '.' || $parts[0] == '..') {
            $parts[] = '';
            $location = $this->location->extractRelative($parts);
        } else {
            $location = new arch\Request(implode('/', $parts).'/');
        }

        $path = trim($name, '/');
        return $location;
    }

    public function extractThemeId(&$path, $findDefault=false) {
        $themeId = null;

        if(false !== strpos($path, '#')) {
            $parts = explode('#', $path, 2);
            $path = array_pop($parts);
            $themeId = trim(array_shift($parts), '/');

            if(empty($themeId)) {
                $themeId = null;
            }
        }

        if($themeId === null && $findDefault) {
            $themeId = aura\theme\Config::getInstance()->getThemeIdFor($this->location->getArea());
        }

        return $themeId;
    }



// Helpers
    protected function _loadHelper($name) {
        switch($name) {
            case 'dispatchContext':
                return $this->getDispatchContext();

            case 'request':
                return $this->request;

            case 'location':
                return $this->location;

            case 'controller':
                return $this->getController();

            case 'scaffold':
                return $this->getScaffold();

            default:
                return $this->loadRootHelper($name);
        }
    }

    public function getController() {
        return Controller::factory($this);
    }

    public function getScaffold() {
        return arch\scaffold\Base::factory($this);
    }


// Dump
    public function getDumpProperties() {
        return [
            'request' => $this->request,
            'location' => $this->location
        ];
    }
}