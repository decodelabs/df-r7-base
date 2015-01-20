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
        $this->values->applicationName = (string)$name;
        return $this;
    }
    
    public function getApplicationName() {
        return $this->values->get('applicationName', 'My Application');
    }
    
    
// Prefix
    public function setUniquePrefix($prefix=null) {
        if($prefix === null) {
            $prefix = core\string\Generator::random(3, 3);
        }
        
        $this->values->uniquePrefix = strtolower((string)$prefix);
        return $this;
    }
    
    public function getUniquePrefix() {
        if(!isset($this->values['uniquePrefix'])) {
            $this->setUniquePrefix();
            $this->save();
        }
        
        return $this->values['uniquePrefix'];
    }
    
    
// PassKey
    public function setPassKey($passKey=null) {
        if($passKey === null) {
            $passKey = core\string\Generator::passKey();
        }
        
        $this->values->passKey = (string)$passKey;
        return $this;
    }
    
    public function getPassKey() {
        if(!isset($this->values['passKey'])) {
            $this->setPassKey();
            $this->save();
        }
        
        return $this->values['passKey'];
    }
    
    
// Packages
    public function getActivePackages() {
        $output = [];
        
        if($this->values->packages->isEmpty()) {
            return $output;
        }
        
        foreach($this->values->packages as $name => $enabled) {
            if($enabled->getValue()) {
                $output[] = $name;
            }
        }
        
        return $output;
    }
}