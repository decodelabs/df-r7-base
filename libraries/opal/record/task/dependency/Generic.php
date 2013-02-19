<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task\dependency;

use df;
use df\core;
use df\opal;

class Generic implements opal\record\task\IDependency {
    
    use opal\record\task\TDependency;

    public function __construct(opal\record\task\ITask $requiredTask) {
        $this->_requiredTask = $requiredTask;
    }
} 