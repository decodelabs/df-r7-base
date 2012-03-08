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

class Context implements IContext, core\IDumpable {
    
    use core\THelperProvider;
    
    protected $_request;
    protected $_locale;
    protected $_application;
    
    public static function factory(core\IApplication $application, $request=null) {
        if(!empty($request)) {
            $request = arch\Request::factory($request);
        } else if($application instanceof arch\IContextAware) {
            $request = $application->getContext()->getRequest();
        } else {
            $request = new arch\Request('/');
        }

        return new self($application, $request); 
    }
    
    public function __construct(core\IApplication $application, arch\IRequest $request) {
        $this->_application = $application;
        $this->_request = $request;
    } 
    
    public function spawnInstance($request=null) {
        if($request === null) {
            return $this;
        }
        
        $request = arch\Request::factory($request);
        
        if($request->eq($this->_request)) {
            return $this;
        }
        
        return new self($this->_application, $request);
    }
    
    
    
// Application
    public function getApplication() {
        return $this->_application;
    }
    
    public function getRunMode() {
        return $this->_application->getRunMode();
    }
    
    public function getDispatchContext() {
        if(!$this->_application instanceof IContextAware) {
            throw new RuntimeException(
                'Current application is not context aware'
            );
        }
        
        return $this->_application->getContext();
    }
    
    public function isDispatchContext() {
        return $this->getDispatchContext() === $this;
    }
    
    
// Requests
    public function getRequest() {
        return $this->_request;
    }
    
    public function getDispatchRequest() {
        return $this->getDispatchContext()->getRequest();
    }
    
    public function normalizeOutputUrl($uri, $toRequest=false) {
        if($toRequest && $uri instanceof IRequest) {
            return $uri;
        } else if($uri instanceof core\uri\IUrl && !$uri instanceof IRequest) {
            return $uri;
        }
        
        if($uri === null) {
            $uri = $this->_request;
        }
        
        if(is_string($uri)) {
            $parts = explode('://', $uri, 2);
            array_pop($parts);
            
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
        
        if($toRequest) {
            if(!$uri instanceof IRequest) {
                $uri = new Request($uri);
            }
            
            return $uri;
        }
        
        if($this->_application instanceof core\IRoutedDirectoryRequestApplication) {
            if(!$uri instanceof IRequest) {
                $uri = new Request($uri);
            }
            
            $uri = $this->_application->requestToUrl($uri);
        } else {
            $uri = new core\uri\Url($uri);
        }
        
        return $uri;
    }


// Misc members
    public function setLocale($locale) {
        if($locale === null) {
            $this->_locale = null;
        } else {
            $this->_locale = core\i18n\Locale::factory($locale);
        }
        
        return $this;
    }
    
    public function getLocale() {
        if($this->_locale) {
            return $this->_locale;
        } else {
            return core\i18n\Manager::getInstance($this->_application)->getLocale(); 
        }
    }
    
    
// Helpers
    public function throwError($code=500, $message='') {
        throw new \Exception($message, (int)$code);
    }
    
    public function findFile($path) {
        return df\Launchpad::$loader->findFile($path);
    }
    
    protected function _loadHelper($name) {
        $class = 'df\\plug\\context\\'.$this->_application->getRunMode().$name;
        
        if(!class_exists($class)) {
            $class = 'df\\plug\\context\\'.$name;
            
            if(!class_exists($class)) {
                
                throw new HelperNotFoundException(
                    'Context helper '.$name.' could not be found'
                );
            }
        }
        
        return new $class($this);
    }
    
    
    public function getI18nManager() {
        return core\i18n\Manager::getInstance($this->_application);
    }
    
    public function getPolicyManager() {
        return core\policy\Manager::getInstance($this->_application);
    }
    
    public function getSystemInfo() {
        return halo\system\Base::getInstance();
    }
    
    public function getUserManager() {
        return user\Manager::getInstance($this->_application);
    }
    
    
    public function __get($key) {
        switch($key) {
            case 'context':
                return $this;
            
            case 'application':
                return $this->_application;
                
            case 'runMode':
                return $this->_application->getRunMode();
                
            case 'dispatchContext':
                return $this->getDispatchContext();

            case 'request': 
                return $this->_request;
                
            case 'dispatchRequest':
                return $this->getDispatchRequest();
                
            case 'locale':
                return $this->getLocale();
                
                
            case 'i18n':
                return core\i18n\Manager::getInstance($this->_application);
                
            case 'policy':
                return core\policy\Manager::getInstance($this->_application);
                
            case 'system':
                return halo\system\Base::getInstance();
                
            case 'user':
                return user\Manager::getInstance($this->_application);
                
            default:
                return $this->getHelper($key);
        }
    }


    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        $translator = core\i18n\translate\Handler::factory('arch/Context', $locale);
        return $translator->_($phrase, $data, $plural);
    }
    
   
    
// Dump
    public function getDumpProperties() {
        return array(
            'request' => $this->_request,
            'application' => $this->_application
        );
    }
}