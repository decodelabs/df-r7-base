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
        $locale = Locale::factory($locale);
        
        if(!$this->isLocaleAllowed($locale)) {
            throw new RuntimeException('Default locale '.$locale.' is not allowed');
        }
        
        $this->values['locale']['default'] = (string)$locale;
        return $this;
    }
    
    public function getDefaultLocale() {
        if(isset($this->values['locale']['default'])) {
            return $this->values['locale']['default'];
        }
        
        return 'en_GB';
    }
    
// Locale filter
    public function setAllowedLocales(array $locales) {
        $allow = [];
        
        foreach($locales as $locale) {
            $allow[] = (string)Locale::factory($locale);
        }
        
        $this->values['locale']['allow'] = $allow;
        return $this;
    }
    
    public function getAllowedLocales() {
        return (array)$this->values['locale']['allow'];
    }
    
    public function setDeniedLocales(array $locales) {
        $deny = [];
        
        foreach($locales as $locale) {
            $deny[] = (string)Locale::factory($locale);
        }
        
        $this->values['locale']['deny'] = $deny;
        return $this;
    }
    
    public function getDeniedLocales() {
        return (array)$this->values['locale']['deny'];
    }
    
    public function isLocaleAllowed($locale) {
        $locale = Locale::factory($locale);
        
        $test = array_unique([
            (string)$locale,
            $locale->getLanguage().'_'.$locale->getRegion(),
            $locale->getLanguage()
        ]);
        
        if(!empty($this->values['locale']['allow'])) {
            $allow = (array)$this->values['locale']['allow'];
            
            foreach($test as $testLocale) {
                if(in_array($testLocale, $allow)) {
                    return true;
                }
            }
            
            return false;
        }
            
        if(!empty($this->values['locale']['deny'])) {
            $allow = (array)$this->values['locale']['deny'];
            
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
            $this->values['locale']['detectClient'] = (bool)$flag;
            return $this;
        }
        
        if(isset($this->values['locale']['detectClient'])) {
            return (bool)$this->values['locale']['detectClient'];
        }
        
        return false;
    }
    
// Translation
    public function isTranslationEnabled($flag=null) {
        if($flag !== null) {
            $this->values['translation']['enabled'] = (bool)$flag;
            return $this;
        }
        
        if(isset($this->values['translation']['enabled'])) {
            return (bool)$this->values['translation']['enabled'];
        }
        
        return false;
    }
}