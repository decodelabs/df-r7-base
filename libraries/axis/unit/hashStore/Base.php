<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\hashStore;

use df;
use df\core;
use df\axis;

abstract class Base extends axis\Unit implements IHashStore {
    
    public function getUnitType() {
        return 'hashStore';
    }
}
