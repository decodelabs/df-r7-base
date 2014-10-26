<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;

    
class Generic implements IOptionalAdapterAwareTask {

    use TTask;
    use TAdapterAwareTask;

    protected $_callback;

    public function __construct($id, $callback, opal\query\IAdapter $adapter=null) {
        $this->_adapter = $adapter;
        $this->_callback = core\lang\Callback::factory($callback);

        $this->_setId($id);
    }

    public function hasAdapter() {
        return $this->_adapter !== null;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function execute(opal\query\ITransaction $transaction) {
        $this->_callback->invoke($this, $transaction);
        return $this;
    }
}