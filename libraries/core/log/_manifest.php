<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log;

use df;
use df\core;


df\Launchpad::loadBaseClass('core/debug/_manifest');


// Exceptions
interface IException {}



// Interfaces
interface IMessageProvider {
    public function getMessage();
}

interface IEntryPoint {
    public function addNode(INode $node);
    public function newGroup($title=null, $file=null, $line=null);

    public function dump($arg1);
    public function dumpDeep($arg1);
    public function dumpQuiet($arg1);
    public function dumpDeepQuiet($arg1);
    public function addDump($dumpObject, core\debug\IStackCall $stackCall, $deep=false, $critical=true);
    public function addDumpList(array $dumpObjects, core\debug\IStackCAll $stackCall, $deep=false, $critical=true);
    
    public function exception(\Exception $exception);
    public function addException(\Exception $exception);

    public function info($message);
    public function todo($message);
    public function warning($message);
    public function error($message);
    public function deprecated();
    public function addMessage($message, $type, core\debug\IStackCall $stackCall);

    public function stub();
    public function stubQuiet();
    public function addStub(array $dumpObjects, core\debug\IStackCall $stackCall, $critical=true);

    public function stackTrace($rewind=0);
}

trait TEntryPoint {

// Entry point
    public function newGroup($title=null, $file=null, $line=null) {
        return new self($title, $file, $line);
    }
    
    
// Dump
    public function dump($arg1) {
        return $this->addDumpList(func_get_args(), core\debug\StackCall::factory(1), false, true);
    }
    
    public function dumpDeep($arg1) {
        return $this->addDumpList(func_get_args(), core\debug\StackCall::factory(1), true, true);
    }
    
    public function dumpQuiet($arg1) {
        return $this->addDumpList(func_get_args(), core\debug\StackCall::factory(1), false, false);
    }

    public function dumpDeepQuiet($arg1) {
        return $this->addDumpList(func_get_args(), core\debug\StackCall::factory(1), true, false);
    }

    public function addDump($dumpObject, core\debug\IStackCall $stackCall, $deep=false, $critical=true) {
        df\Launchpad::loadBaseClass('core/log/node/Dump');
        return $this->addNode(new core\log\node\Dump($dumpObject, $deep, $critical, $stackCall->getFile(), $stackCall->getLine()));
    }
    
    public function addDumpList(array $dumpObjects, core\debug\IStackCAll $stackCall, $deep=false, $critical=true) {
        if(count($dumpObjects) == 1) {
            $object = array_shift($dumpObjects);
            return $this->addDump($object, $stackCall, $deep, $critical);
        }
        
        $group = $this->newGroup('Dump group', $stackCall->getFile(), $stackCall->getLine());
        
        foreach(array_keys($dumpObjects) as $i) {
            if($dumpObjects[$i] instanceof \Exception) {
                $group->addException($dumpObjects[$i]);
            } else {
                $group->addDump($dumpObjects[$i], $stackCall, $deep, $critical);
            }
        }

        $this->addNode($group);
        
        return $this;
    }
    
    
// Exception
    public function exception(\Exception $exception) {
        return $this->addException($exception);
    }
    
    public function addException(\Exception $exception) {
        df\Launchpad::loadBaseClass('core/log/node/Exception');
        $this->addNode(new core\log\node\Exception($exception));
        
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
        $this->addNode(new core\log\node\Message($message, $type, $stackCall->getFile(), $stackCall->getLine()));
        
        return $this;
    }
    
    
// Stub
    public function stub() {
        return $this->addStub(func_get_args(), core\debug\StackCall::factory(1), true);
    }

    public function stubQuiet() {
        return $this->addStub(func_get_args(), core\debug\StackCall::factory(1), false);
    }
    
    public function addStub(array $dumpObjects, core\debug\IStackCall $stackCall, $critical=true) {
        df\Launchpad::loadBaseClass('core/log/node/Stub');
        
        $message = $stackCall->getSignature().' is not yet implemented';
        $stub = new core\log\node\Stub($message, $critical, $stackCall->getFile(), $stackCall->getLine());
        $this->addNode($stub);
        
        foreach($dumpObjects as $dumpObject) {
            $stub->addDump($dumpObject, $stackCall, false, false);
        }
        
        return $this;
    }
    
    public function stackTrace($rewind=0) {
        df\Launchpad::loadBaseClass('core/debug/StackTrace');
        df\Launchpad::loadBaseClass('core/debug/StackCall');
        
        $this->addNode(core\debug\StackTrace::factory($rewind + 1));
        return $this;
    }
}

interface INode extends core\debug\ILocationProvider {
    public function getNodeTitle();
    public function getNodeType();
    public function isCritical();
}

interface IGroupNode extends INode, IEntryPoint, core\IArrayProvider {
    public function setNodeTitle($title);
    public function addChild(INode $node);
    public function getChildren();
    public function hasChildren();
    public function clearChildren();
    public function getNodeCounts();
}

interface IMessageNode extends INode, IMessageProvider {

    const INFO = 1;
    const TODO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const DEPRECATED = 5;

    public function getType();
}

interface IDumpNode extends INode {
    public function &getObject();
    public function isDeep();
}

interface IExceptionNode extends INode {
    public function getException();
    public function getExceptionClass();
    public function getCode();
    public function getMessage();
    public function getStackTrace();
    public function getStackCall();
}

interface IStubNode extends IGroupNode, IMessageProvider {}


interface IWriterProvider {

    public function addWriter(IWriter $writer);
    public function removeWriter(IWriter $writer);
    public function getWriters();
}

interface IHandler extends IEntryPoint, IWriterProvider {

}

trait TWriterProvider {

    protected $_writers = array();

    public function addWriter(IWriter $writer) {
        $this->_writers[$writer->getId()] = $writer;
        return $this;
    }

    public function removeWriter(IWriter $writer) {
        unset($this->_writers[$writer->getId()]);
        return $this;
    }

    public function getWriters() {
        return $this->_writers;
    }
}


interface IWriter {
    public function getId();
    public function writeNode(IHandler $handler, INode $node);
    public function flush(core\log\IHandler $handler);
}