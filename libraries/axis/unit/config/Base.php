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
    
    use axis\TUnit;
    
    protected function __construct(axis\IModel $model) {
        $this->_model = $model;
        $id = static::ID;

        if($id === null) {
            $id = 'model/'.$model->getModelName().'.'.$this->getCanonicalUnitName();
        }

        parent::__construct($id);
    }
    
    public function getUnitType() {
        return 'config';
    }
}
