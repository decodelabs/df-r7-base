<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use df\arch;

use DecodeLabs\Genesis;

class TaskClearBuild extends arch\node\Task
{
    public function execute()
    {
        $this->ensureDfSource();

        Genesis::$build->getHandler()->clear();
    }
}
