<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\log\criticalError;

use df;
use df\core;
use df\axis;
use df\opal;

class Record extends opal\record\Base {
    
    public function fetchFrequency() {
        return $this->getRecordAdapter()->select('COUNT(*) as count')
            ->where('exceptionType', '=', $this['exceptionType'])
            ->where('message', '=', $this['message'])
            ->toValue('count');
    }
}