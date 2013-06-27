<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\extension;

use df;
use df\core;
use df\axis;
    
abstract class Base implements axis\IUnit {

    use axis\TUnit;

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
    }
    
    public function getUnitType() {
        return 'extension';
    }
}