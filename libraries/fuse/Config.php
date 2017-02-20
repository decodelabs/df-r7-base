<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fuse;

use df;
use df\core;
use df\aura;
use df\arch;

class Config extends core\Config {

    const ID = 'Fuse';

    public function getDefaultValues() {
        return [
            'dependencies' => []
        ];
    }

    public function getDependencies() {
        return $this->values->dependencies->toArray();
    }
}