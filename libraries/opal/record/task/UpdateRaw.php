<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class UpdateRaw extends Base implements IUpdateTask {
    
    protected $_primaryManifest;
    protected $_values;
    protected $_adapter;
    
    public function __construct(opal\query\IAdapter $adapter, opal\record\IPrimaryManifest $primaryManifest, array $values) {
        $this->_primaryManifest = $primaryManifest;
        $this->_values = $values;
        $this->_adapter = $adapter;
        
        parent::__construct(self::extractRecordId($primaryManifest));
    }
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function setValues(array $values) {
        $this->_values = $values;
        return $this;
    }
    
    public function getValues() {
        return $this->_values;
    }
    
    public function execute(opal\query\ITransaction $transaction) {
        if($this->_primaryManifest->isNull()) {
            return $this;
        }
        
        $query = $transaction->update($this->_values)->in($this->_adapter);
        
        foreach($this->_primaryManifest->toArray() as $field => $value) {
            $query->where($field, '=', $value);
        }
        
        $query->execute();
        
        return $this;
    }
}
