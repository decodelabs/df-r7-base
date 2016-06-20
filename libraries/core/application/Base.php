<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

abstract class Base implements core\IApplication, core\IDumpable {

    const RUN_MODE = null;

    protected $_isRunning = false;
    protected $_registry = [];
    protected $_dispatchException;

    public static function getApplicationPath() {
        return df\Launchpad::$applicationPath;
    }

    public function getLocalStoragePath() {
        return df\Launchpad::$applicationPath.'/data/local';
    }

    public function getSharedStoragePath() {
        return df\Launchpad::$applicationPath.'/data/shared';
    }

    public function getName() {
        return df\Launchpad::$applicationName;
    }

    public function getUniquePrefix() {
        return df\Launchpad::$uniquePrefix;
    }

    public function getPassKey() {
        return df\Launchpad::$passKey;
    }

    public function getEnvironmentId() {
        return df\Launchpad::$environmentId;
    }

    public function getEnvironmentMode() {
        return df\Launchpad::getEnvironmentMode();
    }

    public function isDevelopment(): bool {
        return df\Launchpad::isDevelopment();
    }

    public function isTesting(): bool {
        return df\Launchpad::isTesting();
    }

    public function isProduction(): bool {
        return df\Launchpad::isProduction();
    }

    public function getRunMode() {
        if(static::RUN_MODE !== null) {
            return static::RUN_MODE;
        }

        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function isDistributed() {
        return df\Launchpad::$isDistributed;
    }


// Dispatch
    public function shutdown() {
        foreach($this->_registry as $object) {
            if($object instanceof core\IShutdownAware) {
                $object->onApplicationShutdown();
            }
        }
    }

    public function getDispatchException() {
        return $this->_dispatchException;
    }


// Debug
    public function renderDebugContext(core\debug\IContext $context) {
        df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
        echo (new core\debug\renderer\PlainText($context))->render();

        return $this;
    }

// Cache objects
    public function setRegistryObject(core\IRegistryObject $object) {
        $this->_registry[$object->getRegistryObjectKey()] = $object;
        return $this;
    }

    public function getRegistryObject($key) {
        if(isset($this->_registry[$key])) {
            return $this->_registry[$key];
        }

        return null;
    }

    public function hasRegistryObject($key) {
        return isset($this->_registry[$key]);
    }

    public function removeRegistryObject($key) {
        if($key instanceof core\IRegistryObject) {
            $key = $key->getRegistryObjectKey();
        }

        unset($this->_registry[$key]);
        return $this;
    }

    public function findRegistryObjects($beginningWith) {
        $output = [];

        foreach($this->_registry as $key => $object) {
            if(0 === strpos($key, $beginningWith)) {
                $output[$key] = $object;
            }
        }

        return $output;
    }

    public function getRegistryObjects() {
        return $this->_registry;
    }


// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->getName(),
            'path' => $this->getApplicationPath(),
            'environmentId' => $this->getEnvironmentId(),
            'environmentMode' => $this->getEnvironmentMode(),
            'runMode' => $this->getRunMode(),
            'registry' => count($this->_registry)
        ];
    }
}