<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

abstract class HandlerBase extends halo\event\HandlerBase implements IHandler {
    
    protected function _registerBinding(halo\event\IBinding $binding) {
        $this->_dispatcher->regenerateMaps();
    }
    
    protected function _unregisterBinding(halo\event\IBinding $binding) {
        $this->_dispatcher->regenerateMaps();
    }
    
    public function freeze(halo\event\IBinding $binding) {
        $binding->isAttached(false);
        return $this;
    }
    
    public function unfreeze(halo\event\IBinding $binding) {
        $binding->isAttached(true);
        return $this;
    }
    
    protected function _getEventTimeout() {
        return -1;
    }
}
