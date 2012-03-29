<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task\dependency;

use df;
use df\core;
use df\opal;


// Interfaces
interface IDependency {
    public function getId();
    public function getParentFields();
    public function getRequiredTask();
    public function getRequiredTaskId();
    public function applyResolution(opal\query\record\task\ITask $dependentTask);
    public function resolve(opal\query\record\task\ITaskSet $taskSet, opal\query\record\task\ITask $dependentTask);
}
