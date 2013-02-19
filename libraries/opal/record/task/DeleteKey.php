<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

class DeleteKey extends Base implements IDeleteKeyTask {
    
    protected $_keys = array();
    protected $_adapter;
    
    public function __construct(opal\query\IAdapter $adapter, array $keys) {
        $this->_keys = $keys;
        $this->_adapter = $adapter;
        
        parent::__construct(implode(opal\record\PrimaryManifest::COMBINE_SEPARATOR, $keys));
    }
    
    public function getKeys() {
        return $this->_keys;
    }
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function execute(opal\query\ITransaction $transaction) {
        $query = $transaction->delete()->from($this->_adapter);
        
        foreach($this->_keys as $key => $value) {
            $query->where($key, '=', $value);
        }
        
        $query->execute();
        return $this;
    }
}
