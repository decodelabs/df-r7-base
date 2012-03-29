<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

class ReplaceRecord extends Base implements IReplaceRecordTask {
    
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
        
        $id = $transaction->replace($data)
            ->in($this->getAdapter())
            ->execute();
            
        $this->_record->acceptChanges($id);
        
        return $this;
    }
}
