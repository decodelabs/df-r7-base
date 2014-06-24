<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\restApi;

use df;
use df\core;
use df\arch;

abstract class Action extends arch\Action implements IAction {
    
    const DEFAULT_TYPE = 'json';

    public function dispatch() {
        core\dump($this);
    }
}