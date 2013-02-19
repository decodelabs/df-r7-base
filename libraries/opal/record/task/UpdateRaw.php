<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class UpdateRaw implements IUpdateTask {
    
    use TTask;
    use TAdapterAwareTask;

    protected $_primaryManifest;
    protected $_values;
    
    public function __construct(opal\query\IAdapter $adapter, opal\record\IPrimaryManifest $primaryManifest, array $values) {
        $this->_primaryManifest = $primaryManifest;
        $this->_values = $values;
        $this->_adapter = $adapter;
        
        $this->_setId(opal\record\Base::extractRecordId($primaryManifest));
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
