<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\log\notFound;

use df;
use df\core;
use df\axis;
use df\opal;

class Record extends opal\record\Base {
    
    public function fetchFrequency() {
        return $this->getRecordAdapter()->select('COUNT(*) as count')
            ->where('mode', '=', $this['mode'])
            ->where('request', '=', $this['request'])
            ->toValue('count');
    }
}