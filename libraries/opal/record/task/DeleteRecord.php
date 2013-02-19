<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class DeleteRecord implements IDeleteRecordTask {
    
    use TTask;
    use TRecordTask;
    
    public function __construct(opal\record\IRecord $record) {
        $this->_record = $record;
        $this->_setId(opal\record\Base::extractRecordId($record));
    }
    
    public function getRecordTaskName() {
        return 'Delete';
    }

    public function execute(opal\query\ITransaction $transaction) {
        if($this->_record->isNew()) {
            return $this;
        }
        
        $query = $transaction->delete()->from($this->getAdapter());
        $manifest = $this->_record->getPrimaryManifest();
        
        if(!$manifest->isNull()) {
            foreach($manifest->toArray() as $field => $value) {
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
        $this->_record->makeNew();
        
        return $this;
    }
}
