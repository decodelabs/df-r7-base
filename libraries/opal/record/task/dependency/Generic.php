<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task\dependency;

use df;
use df\core;
use df\opal;
use df\mesh;

class Generic extends mesh\job\Dependency implements opal\record\task\IDependency {

    use opal\record\task\TDependency;

    public function __construct(mesh\job\IJob $requiredTask) {
        $this->_requiredTask = $requiredTask;
    }
}