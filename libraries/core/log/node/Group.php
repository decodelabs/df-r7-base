<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

class Group implements core\log\IGroupNode {
    
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
    
    public function addChild(core\log\INode $node) {
        $this->_children[] = $node;
        return $this;
    }
    
    public function getChildren() {
        return $this->_children;
    }
    
    public function hasChildren() {
        return !empty($this->_children);
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
    
    public function addDump($dumpObject, $deep=false, core\debug\IStackCall $stackCall) {
        df\Launchpad::loadBaseClass('core/log/node/Dump');
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
        df\Launchpad::loadBaseClass('core/log/node/Exception');
        $this->addChild(new Exception($exception));
        
        return $this;
    }
    
    
// Messages
    public function info($message) {
        return $this->addMessage($message, IMessageNode::INFO, core\debug\StackCall::factory(1));
    }
    
    public function todo($message) {
        return $this->addMessage($message, IMessageNode::TODO, core\debug\StackCall::factory(1));
    }
    
    public function warning($message) {
        return $this->addMessage($message, IMessageNode::WARNING, core\debug\StackCall::factory(1));
    }
    
    public function error($message) {
        return $this->addMessage($message, IMessageNode::ERROR, core\debug\StackCall::factory(1));
    }
    
    public function deprecated() {
        $call = core\debug\StackCall::factory(1);
        
        return $this->addMessage(
            $call->getSignature().' is deprecated',
            IMessageNode::DEPRECATED, 
            $call
        );
    }
    
    public function addMessage($message, $type, core\debug\IStackCall $stackCall) {
        df\Launchpad::loadBaseClass('core/log/node/Message');
        $this->addChild(new Message($message, $type, $stackCall->getFile(), $stackCall->getLine()));
        
        return $this;
    }
    
    
// Stub
    public function stub() {
        return $this->addStub(func_get_args(), core\debug\StackCall::factory(1));
    }
    
    public function addStub(array $dumpObjects, core\debug\IStackCall $stackCall) {
        df\Launchpad::loadBaseClass('core/log/node/Stub');
        
        $message = $stackCall->getSignature().' is not yet implemented';
        $stub = new Stub($message, $stackCall->getFile(), $stackCall->getLine());
        $this->addChild($stub);
        
        foreach($dumpObjects as $dumpObject) {
            $stub->addDump($dumpObject, false, $stackCall);
        }
        
        return $this;
    }
    
    public function stackTrace($rewind=0) {
        df\Launchpad::loadBaseClass('core/debug/StackTrace');
        df\Launchpad::loadBaseClass('core/debug/StackCall');
        
        $this->addChild(core\debug\StackTrace::factory($rewind + 1));
        return $this;
    }


// Nodes
    public function getNodeCounts() {
        $output = [
            'info' => 0,
            'todo' => 0,
            'warning' => 0,
            'error' => 0,
            'deprecated' => 0,
            'stub' => 0,
            'dump' => 0,
            'exception' => 0,
            'stackTrace' => 0,
            'group' => 0
        ];
        
        $this->_countNodes($this, $output);
        return $output;
    }
    
    private function _countNodes(IGroupNode $node, &$counts) {
        foreach($node->getChildren() as $child) {
            $counts[$child->getNodeType()]++;
            
            if($child instanceof IGroupNode) {
                $this->_countNodes($child, $counts);
            }
        }
    }
}