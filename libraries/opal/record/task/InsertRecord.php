<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class InsertRecord implements IInsertRecordTask {
    
    use TTask;
    use TRecordTask;

    protected $_ifNotExists = false;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }
    
    public function getRecordTaskName() {
        return 'Insert';
    }

    public function ifNotExists($flag=null) {
        if($flag !== null) {
            $this->_ifNotExists = (bool)$flag;
            return $this;
        }

        return $this->_ifNotExists;
    }
    
    public function execute(opal\query\ITransaction $transaction) {
        $data = $this->_record->getValuesForStorage();
        
        $query = $transaction->insert($data)
            ->into($this->getAdapter())
            ->ifNotExists((bool)$this->_ifNotExists);
            
        $id = $query->execute();
        $row = $query->getRow();
        
        $this->_record->acceptChanges($id, $row);
        
        return $this;
    }
}
