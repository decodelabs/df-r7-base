<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task\dependency;

use df;
use df\core;
use df\opal;


// Interfaces
interface IDependency {
    public function getId();
    public function getParentFields();
    public function getRequiredTask();
    public function getRequiredTaskId();
    public function applyResolution(opal\record\task\ITask $dependentTask);
    public function resolve(opal\record\task\ITaskSet $taskSet, opal\record\task\ITask $dependentTask);
}
