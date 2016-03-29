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

class Insert extends mesh\job\Base implements opal\record\IJob {

    use opal\record\TJob;

    protected $_ifNotExists = false;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }

    public function getRecordJobName() {
        return 'Insert';
    }

    public function ifNotExists(bool $flag=null) {
        if($flag !== null) {
            $this->_ifNotExists = $flag;
            return $this;
        }

        return $this->_ifNotExists;
    }

    public function execute() {
        $data = $this->_record->getValuesForStorage();

        $query = $this->getAdapter()->insert($data)
            ->ifNotExists((bool)$this->_ifNotExists);

        if($this->_record instanceof opal\record\ILocationalRecord) {
            $query->inside($this->_record->getQueryLocation());
        }

        $id = $query->execute();
        $row = $query->getRow();

        $this->_record->acceptChanges($id, $row);

        return $this;
    }
}
