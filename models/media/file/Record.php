<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\file;

use df;
use df\core;
use df\axis;
use df\opal;

class Record extends opal\record\Base {

    public function getDownloadUrl() {
        return $this->getAdapter()->getModel()->getDownloadUrl($this['id']);
    }

    public function getImageUrl($transformation=null) {
        return $this->getAdapter()->getModel()->getImageUrl($this['id'], $transformation);
    }
}
