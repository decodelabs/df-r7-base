<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;
use df\mesh;

class ReplaceRecord extends mesh\job\Base implements IReplaceRecordTask {

    use TRecordTask;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }

    public function getRecordTaskName() {
        return 'Replace';
    }

    public function execute() {
        $data = $this->_record->getValuesForStorage();

        $id = $this->getAdapter()->replace($data)->execute();
        $this->_record->acceptChanges($id);

        return $this;
    }
}
