<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class DeleteKey implements IDeleteKeyTask {
    
    use TTask;
    use TAdapterAwareTask;

    protected $_keys = array();
    protected $_filterKeys = array();
    
    public function __construct(opal\query\IAdapter $adapter, array $keys) {
        $this->_keys = $keys;
        $this->_adapter = $adapter;
        
        $this->_setId(implode(opal\record\PrimaryManifest::COMBINE_SEPARATOR, $keys));
    }
    

// Keys
    public function setKeys(array $keys) {
        $this->_keys = array();
        return $this->addKeys($keys);
    }

    public function addKeys(array $keys) {
        foreach($keys as $key => $value) {
            $this->addKey($key, $value);
        }

        return $this;
    }

    public function addKey($key, $value) { 
        $this->_keys[$key] = $value;
        return $this;
    }

    public function getKeys() {
        return $this->_keys;
    }


// Filter keys
    public function setFilterKeys(array $filterKeys) {
        $this->_filterKeys = array();
        return $this->addFilterKeys($filterKeys);
    }

    public function addFilterKeys(array $keys) {
        foreach($keys as $key => $value) {
            $this->addFilterKey($key, $value);
        }

        return $this;
    }

    public function addFilterKey($key, $value) {
        $this->_filterKeys[$key] = $value;
        return $this;
    }

    public function getFilterKeys() {
        return $this->_filterKeys;
    }
    
    public function execute(opal\query\ITransaction $transaction) {
        $query = $transaction->delete()->from($this->_adapter);
        
        foreach($this->_keys as $key => $value) {
            $query->where($key, '=', $value);
        }

        if(!empty($this->_filterKeys)) {
            foreach($this->_filterKeys as $key => $value) {
                $query->where($key, '!=', $value);
            }
        }
        
        $query->execute();
        return $this;
    }
}
