<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\version;

use df;
use df\core;
use df\axis;
use df\opal;

class Record extends opal\record\Base {

    protected function _onPreSave($taskSet) {
        if((!$this['number'] || ($this->isNew() && !$this->hasChanged('number')))
        && ($fileId = $this['#file'])) {
            $this->number = $this->getAdapter()->select('MAX(number) as number')
                ->where('file', '=', $fileId)
                ->toValue('number')
                + 1;
        }
    }

    public function getDownloadUrl() {
        return $this->getAdapter()->getModel()->getVersionDownloadUrl($this['#file'], $this['id'], $this['isActive']);
    }

    public function getImageUrl($transformation=null) {
        return $this->getAdapter()->getModel()->getVersionImageUrl($this['#file'], $this['id'], $this['isActive'], $transformation);
    }
}
