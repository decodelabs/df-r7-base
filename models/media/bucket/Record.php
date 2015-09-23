<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\bucket;

use df;
use df\core;
use df\axis;
use df\opal;
use df\neon;

class Record extends opal\record\Base {
    
    public function getHandler() {
        return neon\bucket\Base::factory($this['slug']);
    }
}
