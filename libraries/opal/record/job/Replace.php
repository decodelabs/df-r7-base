<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\job;

use df;
use df\core;
use df\opal;
use df\mesh;

class Replace extends mesh\job\Base implements opal\record\IJob {

    use opal\record\TJob;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }

    public function getRecordJobName() {
        return 'Replace';
    }

    public function execute() {
        $data = $this->_record->getValuesForStorage();

        $id = $this->getAdapter()->replace($data)->execute();
        $this->_record->acceptChanges($id);

        return $this;
    }
}
