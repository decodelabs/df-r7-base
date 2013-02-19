<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class ReplaceRecord extends Base implements IReplaceRecordTask {
    
    use TRecordTask;

    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        parent::__construct(self::extractRecordId($record));
    }
    
    public function getRecordTaskName() {
        return 'Replace';
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
