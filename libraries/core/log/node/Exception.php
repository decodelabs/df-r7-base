<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/dumper/Inspector');

class Exception implements core\log\IExceptionNode {

    protected $_stackCall;
    protected $_exception;

    public function __construct(\Throwable $exception) {
        $this->_exception = $exception;
    }

    public function getNodeTitle() {
        return 'Exception #'.$this->_exception->getCode();
    }

    public function getNodeType() {
        return 'exception';
    }

    public function getFile() {
        return $this->_exception->getFile();
    }

    public function getLine() {
        return $this->_exception->getLine();
    }

    public function isCritical() {
        return df\Launchpad::isTesting();
    }

    public function getException() {
        return $this->_exception;
    }

    public function getExceptionClass() {
        return get_class($this->_exception);
    }

    public function getCode() {
        return $this->_exception->getCode();
    }

    public function getMessage() {
        return $this->_exception->getMessage();
    }

    public function getStackTrace() {
        return core\debug\StackTrace::factory(0, $this->_exception->getTrace());
    }

    public function getStackCall() {
        if(!$this->_stackCall) {
            if($this->_exception instanceof core\IError) {
                $this->_stackCall = $this->_exception->getStackCall();
            } else {
                $trace = $this->_exception->getTrace();
                $last = ['file' => $this->getFile(), 'line' => $this->getLine()];

                if($this->_exception instanceof \ErrorException) {
                    $last = array_shift($trace);
                }

                $current = array_shift($trace);
                $current['fromFile'] = @$last['file'];
                $current['fromLine'] = @$last['line'];

                $this->_stackCall = new core\debug\StackCall($current);
            }
        }

        return $this->_stackCall;
    }

    public function inspect() {
        $inspector = new core\debug\dumper\Inspector();
        return $inspector->inspect($this->_exception, false);
    }
}
