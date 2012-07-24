<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

class Config extends core\Config {
    
    const ID = 'application';
    const STORE_IN_MEMORY = true;
    
    public function getDefaultValues() {
        return [
            'applicationName' => 'My Application',
            'uniquePrefix' => strtolower(core\string\Generator::random(3, 3)),
            'passKey' => core\string\Generator::passKey(),
            'packages' => [
                'webCore' => true
            ]
        ];
    }
    
    
// Application name
    public function setApplicationName($name) {
        $this->_values['applicationName'] = (string)$name;
        return $this;
    }
    
    public function getApplicationName() {
        if(isset($this->_values['applicationName'])) {
            return $this->_values['applicationName'];
        }
        
        return 'My Application';
    }
    
    
// Prefix
    public function setUniquePrefix($prefix=null) {
        if($prefix === null) {
            $prefix = core\string\Generator::random(3, 3);
        }
        
        $this->_values['uniquePrefix'] = strtolower((string)$prefix);
        return $this;
    }
    
    public function getUniquePrefix() {
        if(!isset($this->_values['uniquePrefix'])) {
            $this->setUniquePrefix();
            $this->save();
        }
        
        return $this->_values['uniquePrefix'];
    }
    
    
// PassKey
    public function setPassKey($passKey=null) {
        if($passKey === null) {
            $passKey = core\string\Generator::passKey();
        }
        
        $this->_values['passKey'] = (string)$passKey;
        return $this;
    }
    
    public function getPassKey() {
        if(!isset($this->_values['passKey'])) {
            $this->setPassKey();
            $this->save();
        }
        
        return $this->_values['passKey'];
    }
    
    
// Packages
    public function getActivePackages() {
        $output = array();
        
        if(!isset($this->_values['packages']) || !is_array($this->_values['packages'])) {
            return $output;
        }
        
        foreach($this->_values['packages'] as $name => $enabled) {
            if($enabled) {
                $output[] = $name;
            }
        }
        
        return $output;
    }
}