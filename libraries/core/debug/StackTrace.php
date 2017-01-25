<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

class StackTrace implements IStackTrace, core\IDumpable {

    use TLocationProvider;

    protected $_message;
    protected $_calls = [];

    public static function fromException(\Throwable $e) {
        return self::factory(0, $e->getTrace())
            ->setMessage($e->getMessage());
    }

    public static function factory($rewind=0, array $data=null) {
        if($data === null) {
            $data = debug_backtrace();
        }

        $output = [];

        while($rewind > 0) {
            $rewind--;
            array_shift($data);
        }

        $last = array_shift($data);
        $last['fromFile'] = @$last['file'];
        $last['fromLine'] = @$last['line'];

        foreach($data as $callData) {
            $callData['fromFile'] = @$callData['file'];
            $callData['fromLine'] = @$callData['line'];
            $callData['file'] = $last['fromFile'];
            $callData['line'] = $last['fromLine'];

            $output[] = new StackCall($callData);
            $last = $callData;
        }

        return new self($output);
    }

    protected function __construct(array $calls=null) {
        if(!empty($calls)) {
            foreach($calls as $call) {
                if($call instanceof IStackCall) {
                    $this->_calls[] = $call;
                }
            }

            if(isset($this->_calls[0])) {
                $this->_file = $this->_calls[0]->getFile();
                $this->_line = $this->_calls[0]->getLine();
            } else {
                $data = debug_backtrace();
                $this->_file = $data[1]['file'];
                $this->_line = $data[1]['line'];
            }
        }
    }

    public function setMessage(string $message=null) {
        $this->_message = $message;
        return $this;
    }

    public function getMessage() {
        return $this->_message;
    }

    public function toArray(): array {
        $output = [];

        foreach($this->_calls as $call) {
            $output[] = $call->toArray();
        }

        return $output;
    }

    public function toJsonArray() {
        $output = [];

        foreach($this->_calls as $call) {
            $output[] = $call->toJsonArray();
        }

        return $output;
    }

    public function toJson() {
        return json_encode($this->toJsonArray());
    }

    public function getCalls() {
        return $this->_calls;
    }

    public function getFirstCall() {
        return $this->_calls[0];
    }

// Debug node
    public function getNodeTitle() {
        return 'Stack Trace';
    }

    public function getNodeType() {
        return 'stackTrace';
    }

    public function isCritical() {
        return false;
    }


// Helpers
    public function stripDebugEntries() {
        foreach($this->_calls as $call) {
            switch($call->getFunctionName()) {
                case 'dump':
                case 'dumpDeep':
                case 'stub':
                    array_shift($this->_calls);
                    continue 2;
            }

            break;
        }

        return $this;
    }


// Dumpable
    public function getDumpProperties() {
        $output = [];

        foreach($this->_calls as $call) {
            $output[] = new core\debug\dumper\Property(null, $call);
        }

        return $output;
    }
}
