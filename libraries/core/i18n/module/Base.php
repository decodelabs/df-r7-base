<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

abstract class Base implements IModule {
    
    const MODULE_NAME = null;
    
    protected $_locale;
    protected $_data;
    protected $_manager;
    
    public static function factory(core\i18n\IManager $manager, $name, $locale=null) {
        $class = 'df\\core\\i18n\\module\\'.ucfirst($name);
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'Module '.$name.' could not be found'
            );
        }
        
        return new $class($manager, $locale);
    }
    
    public function __construct(core\i18n\IManager $manager, core\i18n\ILocale $locale=null) {
        if($locale === null) {
            $locale = $manager->getLocale();
        }
        
        $this->_locale = $locale;
        $this->_manager = $manager;
    }
    
    public function getModuleName() {
        if(static::MODULE_NAME !== null) {
            return static::MODULE_NAME;
        }
        
        $parts = explode('\\', get_class($this));
        return lcfirst(array_pop($parts));  
    }
    
    protected function _loadData() {
        if(is_array($this->_data)) {
            return;
        }
        
        $this->_data = array();
        $this->_loadLocale('root');
        
        $lang = $this->_locale->getLanguage();
        $script = $this->_locale->getScript();
        $region = $this->_locale->getRegion();
        
        $this->_loadLocale($lang);
        
        if($region) {
            $this->_loadLocale($lang.'_'.$region);
        }
        
        if($script) {
            $this->_loadLocale($lang.'_'.$script);
            
            if($region) {
                $this->_loadLocale($lang.'_'.$script.'_'.$region);
            }
        }
    }
    
    private function _loadLocale($locale) {
        $path = __DIR__.'/data/'.$this->getModuleName().'/'.$locale.'.loc';
        
        if(is_file($path)) {
            $this->_data = $this->_multiMerge($this->_data, include $path);
        }
    }
    
    private function _multiMerge(array $arr1, array $arr2) {
        foreach($arr2 as $key => $val) {
            if(is_array($val)) {
                if(!isset($arr1[$key]) || !is_array($arr1[$key])) {
                    $arr1[$key] = array();
                }
                
                $arr1[$key] = $this->_multiMerge($arr1[$key], $val);
            } else if(is_scalar($val)) {
                if(!isset($arr1[$key]) || !is_array($arr1[$key])) {
                    $arr1[$key] = $val;
                }
            }
        }
        
        return $arr1;
    }
}
