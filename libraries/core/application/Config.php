<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\flex;

class Config extends core\Config {

    const ID = 'application';
    const STORE_IN_MEMORY = true;

    public function getDefaultValues(): array {
        return [
            'applicationName' => 'My Application',
            'uniquePrefix' => strtolower(flex\Generator::random(3, 3)),
            'passKey' => flex\Generator::passKey(),
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
            $prefix = flex\Generator::random(3, 3);
        }

        $this->values->uniquePrefix = strtolower((string)$prefix);
        return $this;
    }

    public function getUniquePrefix(): string {
        if(!isset($this->values['uniquePrefix'])) {
            $this->setUniquePrefix();
            $this->save();
        }

        return $this->values['uniquePrefix'];
    }


// PassKey
    public function setPassKey($passKey=null) {
        if($passKey === null) {
            $passKey = flex\Generator::passKey();
        }

        $this->values->passKey = (string)$passKey;
        return $this;
    }

    public function getPassKey(): string {
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