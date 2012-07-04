<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
    
class Percentage extends Base {

    protected function _init($scale=5) {
    	core\stub('Percentage field type is not fully functional');
    	$this->setScale($scale);
    }
}