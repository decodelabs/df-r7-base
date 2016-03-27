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

class UpdateRecord extends mesh\job\Base implements IUpdateRecordTask {

    use TRecordTask;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }

    public function getRecordTaskName() {
        return 'Update';
    }

    public function execute() {
        $data = $this->_record->getChangesForStorage();
        $query = $this->getAdapter()->update($data);
        $keySet = $this->_record->getOriginalPrimaryKeySet();

        if($this->_record instanceof opal\record\ILocationalRecord) {
            $query->inside($this->_record->getQueryLocation());
        }

        if(!$keySet->isNull()) {
            foreach($keySet->toArray() as $field => $value) {
                $query->where($field, '=', $value);
            }
        } else {
            $order = false;

            foreach($this->_record->getOriginalValuesForStorage() as $key => $value) {
                if(!$order) {
                    $query->limit(1)->orderBy($key);
                    $order = true;
                }

                $query->where($key, '=', $value);
            }
        }

        $query->execute();
        $this->_record->acceptChanges();

        return $this;
    }
}
