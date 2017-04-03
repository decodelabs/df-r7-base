<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\subscription;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Record extends opal\record\Base {

    protected function onPreSave($queue, $job) {
        $this->lastUpdateDate = 'now';
    }
}