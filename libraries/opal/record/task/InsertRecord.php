<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class InsertRecord extends Base implements IInsertRecordTask {
    
    use TRecordTask;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        parent::__construct(self::extractRecordId($record));
    }
    
    public function getRecordTaskName() {
        return 'Insert';
    }
    
    public function execute(opal\query\ITransaction $transaction) {
        $data = $this->_record->getValuesForStorage();
        
        $query = $transaction->insert($data)->into($this->getAdapter());
        $id = $query->execute();
        $row = $query->getRow();
        
        $this->_record->acceptChanges($id, $row);
        
        return $this;
    }
}
