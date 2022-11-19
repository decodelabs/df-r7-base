<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\error;

use df\opal;

class Record extends opal\record\Base
{
    protected function onPreDelete($queue, $job)
    {
        $id = $this['id'];

        $job->addDependency($queue->asap(
            'deleteLogs:' . $id,
            $this->getAdapter()->getModel()->errorLog->delete()
                ->where('error', '=', $id)
        ));
    }
}
