<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\app\_nodes;

use DecodeLabs\Genesis;

use df\arch;

class TaskClearBuild extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();

        Genesis::$build->getHandler()->clear();
    }
}
