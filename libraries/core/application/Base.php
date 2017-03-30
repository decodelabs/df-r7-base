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

    public static function getApplicationPath(): ?string {
        return df\Launchpad::$applicationPath;
    }

    public function getLocalStoragePath(): string {
        return df\Launchpad::$applicationPath.'/data/local';
    }

    public function getSharedStoragePath(): string {
        return df\Launchpad::$applicationPath.'/data/shared';
    }

    public function getName(): string {
        return df\Launchpad::$applicationName;
    }

    public function getUniquePrefix(): string {
        return df\Launchpad::$uniquePrefix;
    }

    public function getPassKey(): string {
        return df\Launchpad::$passKey;
    }

    public function getEnvironmentId(): string {
        return df\Launchpad::$environmentId;
    }

    public function getEnvironmentMode(): string {
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

    public function getRunMode(): string {
        if(static::RUN_MODE !== null) {
            return static::RUN_MODE;
        }

        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function isDistributed(): bool {
        return df\Launchpad::$isDistributed;
    }


// Dispatch
    public function shutdown(): void {
        foreach($this->_registry as $object) {
            if($object instanceof core\IShutdownAware) {
                $object->onApplicationShutdown();
            }
        }
    }

    public function getDispatchException(): ?\Exception {
        return $this->_dispatchException;
    }


// Debug
    public function renderDebugContext(core\debug\IContext $context): void {
        df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
        echo (new core\debug\renderer\PlainText($context))->render();
    }

// Cache objects
    public function setRegistryObject(core\IRegistryObject $object) {
        $this->_registry[$object->getRegistryObjectKey()] = $object;
        return $this;
    }

    public function getRegistryObject(string $key): ?core\IRegistryObject {
        if(isset($this->_registry[$key])) {
            return $this->_registry[$key];
        }

        return null;
    }

    public function hasRegistryObject(string $key): bool {
        return isset($this->_registry[$key]);
    }

    public function removeRegistryObject(string $key) {
        if($key instanceof core\IRegistryObject) {
            $key = $key->getRegistryObjectKey();
        }

        unset($this->_registry[$key]);
        return $this;
    }

    public function findRegistryObjects(string $beginningWith): array {
        $output = [];

        foreach($this->_registry as $key => $object) {
            if(0 === strpos($key, $beginningWith)) {
                $output[$key] = $object;
            }
        }

        return $output;
    }

    public function getRegistryObjects(): array {
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