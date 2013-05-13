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
use df\halo;

class Context implements IContext, \Serializable, core\IDumpable {
    
    use core\TContext;

    public $request;
    public $location;
    
    public static function getCurrent(core\IApplication $application=null) {
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }

        if($application instanceof arch\IContextAware) {
            return $application->getContext();
        }

        return null;
    }

    public static function factory(core\IApplication $application, $request=null) {
        if(!empty($request)) {
            $request = arch\Request::factory($request);
        } else if($application instanceof arch\IContextAware
        && $application->hasContext()) {
            $request = $application->getContext()->location;
        } else {
            $request = new arch\Request('/');
        }

        return new self($application, $request); 
    }
    
    public function __construct(core\IApplication $application, arch\IRequest $request) {
        $this->application = $application;
        $this->location = $request;

        if($this->application instanceof IContextAware 
        && $this->application->hasContext()) {
            $this->request = $this->application->getContext()->location;
        } else {
            $this->request = $request;
        }
    } 
    
    public function spawnInstance($request=null) {
        if($request === null) {
            return $this;
        }
        
        $request = arch\Request::factory($request);
        
        if($request->eq($this->request)) {
            return $this;
        }
        
        return new self($this->application, $request);
    }


    public function serialize() {
        return (string)$this->location;
    }

    public function unserialize($data) {
        $this->location = Request::factory($data);
        $this->application = df\Launchpad::$application;

        if($this->application instanceof IContextAware 
        && $this->application->hasContext()) {
            $this->request = $this->application->getContext()->location;
        } else {
            $this->request = $this->location;
        }

        return $this;
    }
    
    
    
// Application
    public function getDispatchContext() {
        if(!$this->application instanceof IContextAware) {
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
            return $uri;
        }
        
        if($uri === null) {
            $uri = $this->request;
        }
        
        if(is_string($uri)) {
            if(substr($uri, 0, 7) == 'mailto:') {
                return new core\uri\MailtoUrl($uri);
            } else if(substr($uri, 0, 2) == '//') {
                return new halo\protocol\http\Url($uri);
            } else {
                $parts = explode('://', $uri, 2);
                
                if($scheme = array_shift($parts)) {
                    switch(strtolower($scheme)) {
                        case 'http':
                        case 'https':
                            return new halo\protocol\http\Url($uri);
                            
                        case 'ftp':
                            return new halo\protocol\ftp\Url($uri);
                            
                        case 'mailto':
                            return new core\uri\MailtoUrl($uri);
                            
                        case 'directory':
                            $uri = new Request($uri);
                            break;
                    }
                }
            }
        }
        
        if($toRequest) {
            if(!$uri instanceof IRequest) {
                $uri = new Request($uri);
            }
            
            return $this->_applyRequestRedirect($uri, $from, $to);
        }
        
        if($this->application instanceof arch\IRoutedDirectoryRequestApplication) {
            if(!$uri instanceof IRequest) {
                $uri = new Request($uri);
            }
            
            $uri = $this->application->requestToUrl($this->_applyRequestRedirect($uri, $from, $to));
        } else {
            $uri = new core\uri\Url($uri);
        }
        
        return $uri;
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


    
// Helpers
    protected function _loadHelper($name) {
        $class = 'df\\plug\\context\\'.$this->application->getRunMode().$name;
        
        if(!class_exists($class)) {
            $class = 'df\\plug\\context\\'.$name;
            
            if(!class_exists($class)) {
                return null;
            }
        }
        
        return new $class($this);
    }
    
    public function __get($key) {
        switch($key) {
            case 'dispatchContext':
                return $this->getDispatchContext();

            case 'request': 
                return $this->request;
                
            case 'location':
                return $this->location;
                
            default:
                return $this->_getDefaultMember($key);
        }
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
        return array(
            'request' => $this->request,
            'location' => $this->location,
            'application' => $this->application
        );
    }
}