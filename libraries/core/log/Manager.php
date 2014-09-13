<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log;

use df;
use df\core;
use df\axis;

class Manager implements IManager {

    use core\TManager;
    
    const REGISTRY_PREFIX = 'manager://log';

    public function logAccessError($code=403, $request=null, $message=null) {
        $this->_getModel()->accessError($code, $request, $message);
        return $this;
    }

    public function logNotFound($request=null, $message=null) {
        $this->_getModel()->notFound($request, $message);
        return $this;
    }

    public function logException(\Exception $exception, $request=null) {
        $this->_getModel()->exception($exception, $request);
        return $this;
    }

    public function swallow(Callable $block) {
        $args = func_get_args();
        array_shift($args);

        try {
            call_user_func_array($block, $args);
            return true;
        } catch(\Exception $e) {
            $this->logException($e);
            return false;
        }
    }

    protected function _getModel() {
        return axis\Model::factory('log');
    }
}