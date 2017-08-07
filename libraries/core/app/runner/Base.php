<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\runner;

use df;
use df\core;

abstract class Base implements core\IRunner {

    protected $_isRunning = false;
    protected $_dispatchException;


    public static function factory(string $name): core\IRunner {
        $class = 'df\\core\\app\\runner\\'.$name;

        if(!class_exists($class)) {
            throw core\Error::ENotFound('Runner '.$name.' could not be found');
        }

        return new $class();
    }


// Dispatch
    public function getDispatchException(): ?\Throwable {
        return $this->_dispatchException;
    }


// Debug
    public function renderDebugContext(core\debug\IContext $context): void {
        df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
        echo (new core\debug\renderer\PlainText($context))->render();
    }
}
