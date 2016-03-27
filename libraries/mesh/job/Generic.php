<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;

class Generic extends Base {

    use mesh\job\TAdapterAwareJob;

    protected $_callback;

    public function __construct($id, $callback, IJobAdapter $adapter=null) {
        $this->_adapter = $adapter;
        $this->_callback = core\lang\Callback::factory($callback);

        $this->_setId($id);
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function execute() {
        $this->_callback->invoke($this);
        return $this;
    }
}