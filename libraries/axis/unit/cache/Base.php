<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\cache;

use df;
use df\core;
use df\axis;
    
abstract class Base extends core\cache\Base implements axis\IUnit {

    use axis\TUnit;

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
        parent::__construct($model->getApplication());
    }
    
    public function getUnitType() {
        return 'cache';
    }
}