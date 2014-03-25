<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\cache;

use df;
use df\core;
use df\axis;
    
abstract class SessionExtended extends core\cache\SessionExtended implements axis\IUnit, axis\IAdapterBasedUnit {

    use axis\TUnit;

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
        parent::__construct($model->getApplication());
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
}