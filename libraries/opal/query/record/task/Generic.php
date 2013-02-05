<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

    
class Generic extends Base {

    protected $_callback;
    protected $_adapter;

    public function __construct(opal\query\IAdapter $adapter, $id, Callable $callback) {
        $this->_adapter = $adapter;
        $this->_callback = $callback;

        parent::__construct($id);
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function execute(opal\query\ITransaction $transaction) {
        $this->_callback->__invoke($this->_adapter, $transaction);
        return $this;
    }
}