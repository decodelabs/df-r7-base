<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\hashStore;

use df;
use df\core;
use df\axis;

abstract class Base implements IHashStore {
    
    use axis\TUnit;

    public function getUnitType() {
        return 'hashStore';
    }
}
