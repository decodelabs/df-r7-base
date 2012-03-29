<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

class DeleteRecord extends Base implements IDeleteRecordTask {
    
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
