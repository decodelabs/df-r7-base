<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df\core;

class Locale implements ILocale, \Serializable, core\IDumpable {
    
    protected $_language;
    protected $_script;
    protected $_region;
    protected $_variants = array();
    protected $_keywords = array();
    
    public static function factory($locale, core\IApplication $application=null) {
        if($locale instanceof ILocale) {
            return $locale;
        }
        
        if($locale === null || $locale === true) {
            return Manager::getInstance($application)->getLocale();
        }
        
        return new self($locale);
    }
    
    public static function setCurrent($locale, core\IApplication $application=null) {
        return Manager::getInstance($application)->setLocale($locale);
    }
    
    public static function getCurrent(core\IApplication $application) {
        return Manager::getInstance($application)->getLocale();
    }
    
    public function __construct($locale) {
        $parts = \Locale::parseLocale((string)$locale);
        
        foreach($parts as $key => $part) {
            if($key == 'language') {
                $this->_language = $part;
            } else if($key == 'script') {
                $this->_script = $part;
            } else if($key == 'region') {
                $this->_region = $part;
            } else if(substr($key, 0, 7) == 'variant') {
                $this->_variants[] = $part;
            }
        }
        
        if($keywords = \Locale::getKeywords((string)$locale)) {
            $this->_keywords = $keywords;
        }
    }
    
    
// Serialize
    public function serialize() {
        return $this->toString();
    }
    
    public function unserialize($data) {
        $this->__construct($data);
    }
    
    
// Accessors
    public function toString() {
        try {
            $values = array('language' => $this->_language);
            
            if($this->_region !== null) {
                $values['region'] = $this->_region;
            }
            
            if($this->_script !== null) {
                $values['script'] = $this->_script;
            }
            
            if(!empty($this->_variants)) {
                $values['variant'] = $this->_variants;
            }
            
            $output = \Locale::composeLocale($values);
        } catch(\Exception $e) {
            core\debug()->exception($e);
            return $this->_language.'_'.$this->_region;
        }
        
        if(!empty($this->_keywords)) {
            $keywords = array();
            
            foreach($this->_keywords as $key => $value){
                $keywords[] = $key.'='.$value;
            }
            
            $output .= '@'.implode(';', $keywords);
        }
        
        return $output;
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return $this->_language.'_'.$this->_region;
        }
    }
    
    public function getDisplayName($formatLocale=null) {
        if($formatLocale === true) {
            $formatLocale = $this;
        }
        
        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayName((string)$this, (string)$formatLocale);
    }
    
    
    public function getLanguage() {
        return $this->_language;
    }
    
    public function getDisplayLanguage($formatLocale=null) {
        if($formatLocale === true) {
            $formatLocale = $this;
        }
        
        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayLanguage((string)$this, (string)$formatLocale);
    }
    
    public function getScript() {
        return $this->_script;
    }
    
    public function getDisplayScript($formatLocale=null) {
        if($formatLocale === true) {
            $formatLocale = $this;
        }
        
        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayScript((string)$this, (string)$formatLocale);
    }
    
    public function getRegion() {
        return $this->_region;
    }
    
    public function getDisplayRegion($formatLocale=null) {
        if($formatLocale === true) {
            $formatLocale = $this;
        }
        
        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayRegion((string)$this, (string)$formatLocale);
    }
    
    public function getVariants() {
        return $this->_variants;
    }
    
    public function getDisplayVariants($formatLocale=null) {
        if($formatLocale === true) {
            $formatLocale = $this;
        }
        
        $formatLocale = self::factory($formatLocale);
        return \Locale::getDisplayVariant((string)$this, (string)$formatLocale);
    }
    
    public function getKeywords() {
        return $this->_keywords;
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->__toString();
    }
}