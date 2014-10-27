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

    public static function getCurrent() {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\IContextAware) {
            return $application->getContext();
        }

        return null;
    }

    public static function factory($request=null, $runMode=null) {
        $application = df\Launchpad::getApplication();

        if(!empty($request)) {
            $request = arch\Request::factory($request);
        } else if($application instanceof core\IContextAware && $application->hasContext()) {
            $request = $application->getContext()->location;
        } else {
            $request = new arch\Request('/');
        }

        return new self($request, $runMode); 
    }
    
    public function __construct(arch\IRequest $request, $runMode=null) {
        $this->application = df\Launchpad::$application;
        $this->location = $request;
        $this->_runMode = $runMode;

        if($this->application instanceof core\IContextAware 
        && $this->application->hasContext()) {
            $this->request = $this->application->getContext()->location;
        } else {
            $this->request = $request;
        }
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
    
    public function isDispatchContext() {
        return $this->getDispatchContext() === $this;
    }
    
    
// Requests
    public function getRequest() {
        return $this->request;
    }
    
    public function getLocation() {
        return $this->location;
    }
    
    public function normalizeOutputUrl($uri, $toRequest=false, $from=null, $to=null) {
        if($toRequest && $uri instanceof IRequest) {
            return $this->_applyRequestRedirect($uri, $from, $to);
        } else if($uri instanceof core\uri\IUrl && !$uri instanceof IRequest) {
            if($toRequest && $uri instanceof link\http\IUrl && ($request = $uri->getDirectoryRequest())) {
                $uri = $request;
            } else {
                return $uri;
            }
        }
        
        if($uri === null) {
            $uri = $this->request;
        }
        
        if(is_string($uri)) {
            if(substr($uri, 0, 7) == 'mailto:') {
                return new core\uri\MailtoUrl($uri);
            } else if(substr($uri, 0, 2) == '//') {
                return new link\http\Url($uri);
            } else {
                $parts = explode('://', $uri, 2);
                
                if($scheme = array_shift($parts)) {
                    switch(strtolower($scheme)) {
                        case 'http':
                        case 'https':
                            return new link\http\Url($uri);
                            
                        case 'ftp':
                            return new link\ftp\Url($uri);
                            
                        case 'mailto':
                            return new core\uri\MailtoUrl($uri);
                            
                        case 'directory':
                            $uri = new Request($uri);
                            break;
                    }
                }
            }
        }
        
        if(!$uri instanceof IRequest) {
            $uri = new Request($uri);
        }

        $uri = $this->_applyRequestRedirect($uri, $from, $to);

        if($toRequest) {
            return $uri;
        }

        return core\application\http\Router::getInstance()->requestToUrl($uri);
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
                $parts = explode('/', $rem);
            }
        } else {
            $parts = explode('/', $path);
            $name = array_pop($parts);
        }

        if(empty($parts)) {
            $location = clone $this->location;
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

            case 'scaffold':
                return $this->getScaffold();
                
            default:
                return $this->loadRootHelper($name);
        }
    }

    public function getScaffold() {
        return arch\scaffold\Base::factory($this);
    }
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        if($locale === null) {
            $locale = $this->_locale;
        }

        $translator = core\i18n\translate\Handler::factory('arch/Context', $locale);
        return $translator->_($phrase, $data, $plural);
    }

    
// Dump
    public function getDumpProperties() {
        return [
            'request' => $this->request,
            'location' => $this->location
        ];
    }
}