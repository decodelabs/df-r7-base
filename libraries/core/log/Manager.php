<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log;

use df\axis;
use df\core;

class Manager implements core\IManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://log';

    public function logAccessError($code = 403, $request = null, $message = null)
    {
        $this->_getModel()->logAccessError($code, $request, $message);
        return $this;
    }

    public function logNotFound($request = null, $message = null)
    {
        $this->_getModel()->logNotFound($request, $message);
        return $this;
    }

    public function logException(\Throwable $exception, $request = null)
    {
        $this->_getModel()->logException($exception, $request);
        return $this;
    }

    protected function _getModel()
    {
        return axis\Model::factory('pestControl');
    }



    public function swallow($block, ...$args)
    {
        try {
            core\lang\Callback($block, ...$args);
            return true;
        } catch (\Throwable $e) {
            $this->logException($e);
            return false;
        }
    }
}
