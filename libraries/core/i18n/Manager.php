<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df;
use df\core;

class Manager implements IManager {
    
    const REGISTRY_PREFIX = 'manager://i18n';
    
    protected $_locale;
    protected $_application;
    
    public static function getInstance(core\IApplication $application=null) {
        if(!$application) {
            $application = df\Launchpad::getActiveApplication();
        }
        
        if(!$output = $application->_getCacheObject(self::REGISTRY_PREFIX)) {
            $application->_setCacheObject(
                $output = new self($application)
            );
        }
        
        return $output;
    }
    
    protected function __construct(core\IApplication $application) {
        $this->_application = $application;
    }
    
    public function getApplication() {
        return $this->_application;
    }
    
    public function getRegistryObjectKey() {
        return self::REGISTRY_PREFIX;
    }
    
    
// Locale
    public function setLocale($locale) {
        if($locale === null) {
            $locale = $this->getDefaultLocale();
        }
        
        $this->_locale = Locale::factory($locale);
        $string = $this->_locale->__toString();
        
        setlocale(LC_ALL, $string);
        \Locale::setDefault($string);
        
        return $this;
    }
    
    public function getLocale() {
        if($this->_locale === null) {
            $this->_locale = $this->getDefaultLocale();
        }
        
        return $this->_locale;
    }
    
    public function getDefaultLocale() {
        $config = Config::getInstance($this->_application);
        $default = null;
        
        if($config->shouldDetectClientLocale() 
        && $application instanceof core\application\Http) {
            $request = $application->getHttpRequest();
            
            if(isset($request->headers['accept-language'])) {
                $default = \Locale::acceptFromHttp($request->headers['accept-language']);
            }
        }
        
        if(!$default) {
            $default = $config->getDefaultLocale();
        }
        
        if(!$default) {
            $default = \Locale::getDefault();
        }
        
        if(!$default) {
            $default = 'en_GB';
        }
        
        return Locale::factory($default, $this->_application);
    }
    
    
// Modules
    public function getModule($name, $locale=null) {
        // TODO: cache output
        return core\i18n\module\Base::factory($this, $name, $locale);
    }
    
    public function __get($member) {
        switch($member) {
            case 'locale':
                return $this->getLocale();
                
            default:
                return $this->getModule($member);
        }
    }
}
