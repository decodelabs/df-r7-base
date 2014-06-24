<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\cache;

use df;
use df\core;
use df\axis;
    
abstract class Base extends core\cache\Base implements axis\IUnit, axis\IAdapterBasedUnit {

    use axis\TUnit;

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
        parent::__construct();
    }
    
    public function getUnitType() {
        return 'cache';
    }

    public function getUnitAdapter() {
        return $this->getCacheBackend();
    }

    public function getUnitAdapterName() {
        $parts = explode('\\', get_class($this->getCacheBackend()));
        return array_pop($parts);
    }

    public function getUnitAdapterConnectionName() {
        return $this->getCacheBackend()->getConnectionDescription();
    }

    public function getStorageBackendName() {
        return $this->getCacheId();
    }
}