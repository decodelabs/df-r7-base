<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

class InsertRecord extends Base implements IInsertRecordTask {
    
    protected $_record;
    
    public function __construct(opal\query\record\IRecord $record) {
        $this->_record = $record;
        parent::__construct(self::extractRecordId($record));
    }
    
    public function getRecord() {
        return $this->_record;
    }
    
    public function getAdapter() {
        return $this->_record->getRecordAdapter();
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
