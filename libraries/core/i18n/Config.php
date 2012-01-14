<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df\core;

class Config extends core\Config {
    
    const ID = 'I18n';
    
    public function getDefaultValues() {
        return [
            'locale' => [
                'default' => 'en_GB',
                'allow' => [],
                'deny' => [],
                'detectClient' => false
            ],
            'translation' => [
                'enabled' => false
            ]
        ];
    }
    
    
// Default locale
    public function setDefaultLocale($locale) {
        $locale = Locale::factory($this->getApplication(), $locale);
        
        if(!$this->isLocaleAllowed($locale)) {
            throw new RuntimeException('Default locale '.$locale.' is not allowed');
        }
        
        $this->_values['locale']['default'] = (string)$locale;
        return $this;
    }
    
    public function getDefaultLocale() {
        if(isset($this->_values['locale']['default'])) {
            return $this->_values['locale']['default'];
        }
        
        return 'en_GB';
    }
    
// Locale filter
    public function setAllowedLocales(array $locales) {
        $allow = array();
        $application = $this->getApplication();
        
        foreach($locales as $locale) {
            $allow[] = (string)Locale::factory($application, $locale);
        }
        
        $this->_values['locale']['allow'] = $allow;
        return $this;
    }
    
    public function getAllowedLocales() {
        return (array)$this->_values['locale']['allow'];
    }
    
    public function setDeniedLocales(array $locales) {
        $deny = array();
        $application = $this->getApplication();
        
        foreach($locales as $locale) {
            $deny[] = (string)Locale::factory($application, $locale);
        }
        
        $this->_values['locale']['deny'] = $deny;
        return $this;
    }
    
    public function getDeniedLocales() {
        return (array)$this->_values['locale']['deny'];
    }
    
    public function isLocaleAllowed($locale) {
        $locale = Locale::factory($this->getApplication(), $locale);
        
        $test = array_unique(array(
            (string)$locale,
            $locale->getLanguage().'_'.$locale->getRegion(),
            $locale->getLanguage()
        ));
        
        if(!empty($this->_values['locale']['allow'])) {
            $allow = (array)$this->_values['locale']['allow'];
            
            foreach($test as $testLocale) {
                if(in_array($testLocale, $allow)) {
                    return true;
                }
            }
            
            return false;
        }
            
        if(!empty($this->_values['locale']['deny'])) {
            $allow = (array)$this->_values['locale']['deny'];
            
            foreach($test as $testLocale) {
                if(in_array($testLocale, $deny)) {
                    return false;
                }
            }
            
            return true;
        }
        
        return true;
    }
    
// Detect
    public function shouldDetectClientLocale($flag=null) {
        if($flag !== null) {
            $this->_values['locale']['detectClient'] = (bool)$flag;
            return $this;
        }
        
        if(isset($this->_values['locale']['detectClient'])) {
            return (bool)$this->_values['locale']['detectClient'];
        }
        
        return false;
    }
    
// Translation
    public function isTranslationEnabled($flag=null) {
        if($flag !== null) {
            $this->_values['translation']['enabled'] = (bool)$flag;
            return $this;
        }
        
        if(isset($this->_values['translation']['enabled'])) {
            return (bool)$this->_values['translation']['enabled'];
        }
        
        return false;
    }
}