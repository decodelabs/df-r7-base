<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\error;

use df;
use df\core;
use df\apex;
use df\opal;
use df\axis;

class Record extends opal\record\Base {

    protected function _onPreDelete($taskSet, $task) {
        $id = $this['id'];

        $deleteTask = $taskSet->addRawQuery(
            'deleteLogs:'.$id,
            $this->getAdapter()->getModel()->errorLog->delete()
                ->where('error', '=', $id)
        );

        $task->addDependency($deleteTask);
    }
}