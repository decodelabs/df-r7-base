<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\node;

use df;
use df\core;

class Group implements core\debug\IGroupNode {
    
    use core\debug\TLocationProvider;
    
    protected $_title;
    protected $_children = array();
    
    public function __construct($title=null, $file=null, $line=null) {
        $this->setNodeTitle($title);
        $this->_file = $file;
        $this->_line = $line;
    }
    
    public function setNodeTitle($title) {
        if(empty($title)) {
            $title = 'Group';
        }
        
        $this->_title = $title;
        return $this;
    }
    
    public function getNodeTitle() {
        return $this->_title;
    }
    
    public function getNodeType() {
        return 'group';
    }
    
    public function isCritical() {
        foreach($this->_children as $node) {
            if($node->isCritical()) {
                return true;
            }
        }
        
        return false;
    }
    
    
// Children
    public function toArray() {
        return $this->_children;
    }
    
    public function addChild(core\debug\INode $node) {
        $this->_children[] = $node;
        return $this;
    }
    
    public function getChildren() {
        return $this->_children;
    }
    
    public function clearChildren() {
        $this->_children = array();
        return $this;
    }
    
    
// Entry point
    public function newGroup($title=null, $file=null, $line=null) {
        $group = new self($title, $file, $line);
        $this->addChild($group);
        
        return $group;
    }
    
    
// Dump
    public function dump($arg1) {
        return $this->addDumpList(func_get_args(), false, core\debug\StackCall::factory(1));
    }
    
    public function dumpDeep($arg1) {
        return $this->addDumpList(func_get_args(), true, core\debug\StackCall::factory(1));
    }
    
    public function addDump(&$dumpObject, $deep=false, core\debug\IStackCall $stackCall) {
        require_once __DIR__.'/Dump.php';
        return $this->addChild(new Dump($dumpObject, $deep, $stackCall->getFile(), $stackCall->getLine()));
    }
    
    public function addDumpList(array $dumpObjects, $deep=false, core\debug\IStackCAll $stackCall) {
        if(count($dumpObjects) == 1) {
            $object = array_shift($dumpObjects);
            return $this->addDump($object, $deep, $stackCall);
        }
        
        $group = $this->newGroup('Dump group', $stackCall->getFile(), $stackCall->getLine());
        
        foreach(array_keys($dumpObjects) as $i) {
            if($dumpObjects[$i] instanceof \Exception) {
                $group->addException($dumpObjects[$i]);
            } else {
                $group->addDump($dumpObjects[$i], $deep, $stackCall);
            }
        }
        
        return $this;
    }
    
    
// Exception
    public function exception(\Exception $exception) {
        return $this->addException($exception);
    }
    
    public function addException(\Exception $exception) {
        require_once __DIR__.'/Exception.php';
        $this->addChild(new Exception($exception));
        
        return $this;
    }
    
    
// Messages
    public function info($message) {
        return $this->addMessage($message, Message::INFO, core\debug\StackCall::factory(1));
    }
    
    public function todo($message) {
        return $this->addMessage($message, Message::TODO, core\debug\StackCall::factory(1));
    }
    
    public function warning($message) {
        return $this->addMessage($message, Message::WARNING, core\debug\StackCall::factory(1));
    }
    
    public function error($message) {
        return $this->addMessage($message, Message::ERROR, core\debug\StackCall::factory(1));
    }
    
    public function deprecated() {
        $call = core\debug\StackCall::factory(1);
        
        return $this->addMessage(
            $call->getCallSignature().' is deprecated',
            Message::DEPRECATED, 
            $call
        );
    }
    
    public function addMessage($message, $type, core\debug\IStackCall $stackCall) {
        require_once __DIR__.'/Message.php';
        $this->addChild(new Message($message, $type, $stackCall->getFile(), $stackCall->getLine()));
        
        return $this;
    }
    
    
// Stub
    public function stub() {
        return $this->addStub(func_get_args(), core\debug\StackCall::factory(1));
    }
    
    public function addStub(array $dumpObjects, core\debug\IStackCall $stackCall) {
        require_once __DIR__.'/Stub.php';
        
        $message = $stackCall->getCallSignature().' is not yet implemented';
        $stub = new core\debug\node\Stub($message, $stackCall->getFile(), $stackCall->getLine());
        $this->addChild($stub);
        
        foreach($dumpObjects as $dumpObject) {
            $stub->addDump($dumpObject);
        }
        
        return $this;
    }
    
    public function stackTrace($rewind=0) {
        require_once dirname(__DIR__).'/StackTrace.php';
        require_once dirname(__DIR__).'/StackCall.php';
        
        $this->addChild(core\debug\StackTrace::factory($rewind + 1));
        return $this;
    }
}