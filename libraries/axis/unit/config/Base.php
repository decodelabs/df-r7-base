<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\config;

use df;
use df\core;
use df\axis;

abstract class Base extends core\Config implements axis\IUnit {
    
    private $_unitName;
    protected $_model;
    
    public function __construct(axis\IModel $model) {
        $this->_model = $model;
        
        $id = 'model/'.$model->getModelName().'.'.$this->getCanonicalUnitName();
        parent::__construct($model->getApplication(), $id);
    }
    
    public function getUnitName() {
        if(!$this->_unitName) {
            $parts = explode('\\', get_class($this));
            array_pop($parts);
            $this->_unitName = array_pop($parts);
        }
        
        return $this->_unitName;
    }
    
    public function getCanonicalUnitName() {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $this->getUnitName());
    }
    
    public function getUnitId() {
        return $this->_model->getModelName().axis\Unit::ID_SEPARATOR.$this->getUnitName();
    }
    
    public function getModel() {
        return $this->_model;
    }
}
