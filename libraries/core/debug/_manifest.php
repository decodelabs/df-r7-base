<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

// Interfaces
interface ILocationProvider {
    public function getFile();
    public function getLine();
}

trait TLocationProvider {
    
    protected $_file;
    protected $_line;
    
    public function getFile() {
        return $this->_file;
    }
    
    public function getLine() {
        return $this->_line;
    }
}

interface IMessageProvider {
    public function getMessage();
}

interface IEntryPoint {
    public function dump($arg1);
    public function dumpDeep($arg1);
    public function exception(\Exception $exception);
    public function info($message);
    public function todo($message);
    public function warning($message);
    public function error($message);
    public function deprecated();
    public function stub();
    public function stackTrace($rewind=0);
}

interface INode extends ILocationProvider {
    public function getNodeTitle();
    public function getNodeType();
    public function isCritical();
}

interface IGroupNode extends INode, IEntryPoint, core\IArrayProvider {
    public function setNodeTitle($title);
    public function addChild(INode $node);
    public function getChildren();
    public function clearChildren();
    
    public function newGroup($title=null, $file=null, $line=null);
    public function addDump(&$dumpObject, $deep=false, IStackCall $stackCall);
    public function addDumpList(array $dumpObjects, $deep=false, IStackCAll $stackCall);
    public function addException(\Exception $exception);
    public function addMessage($message, $type, IStackCall $stackCall);
    public function addStub(array $dumpObjects, IStackCall $stackCall);
}

interface IMessageNode extends INode, IMessageProvider {
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
    public function getTrace();
    public function getTraceAsString();
    
    public function getStackCall();
}

interface IStubNode extends IGroupNode, IMessageProvider {}

interface IContext extends IGroupNode {
    public function isEnabled();
    public function enable();
    public function disable();
    
    public function setTransport(ITransport $transport);
    public function getTransport();
    public function flush();
}



interface IStackTrace extends INode, core\IArrayProvider {
    
}

interface IStackCall extends ILocationProvider {
    public function getArgs();
    public function hasArgs();
    public function countArgs();
    public function getArgString();
    
    public function getType();
    public function isStatic();
    public function isObject();
    public function isNamespaceFunction();
    public function isGlobalFunction();
    
    public function getNamespace();
    public function hasNamespace();
    
    public function getClass();
    public function hasClass();
    public function getClassName();
    
    public function getFunctionName();
    public function getCallSignature($argString=false);
    
    public function getCallingFile();
    public function getCallingLine();
}
