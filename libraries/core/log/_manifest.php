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

interface INode extends core\debug\ILocationProvider {
    public function getNodeTitle();
    public function getNodeType();
    public function isCritical();
}

interface IGroupNode extends INode, core\debug\IEntryPoint, core\IArrayProvider {
    public function setNodeTitle($title);
    public function addChild(INode $node);
    public function getChildren();
    public function hasChildren();
    public function clearChildren();
    
    public function newGroup($title=null, $file=null, $line=null);
    public function addDump($dumpObject, $deep=false, core\debug\IStackCall $stackCall);
    public function addDumpList(array $dumpObjects, $deep=false, core\debug\IStackCAll $stackCall);
    public function addException(\Exception $exception);
    public function addMessage($message, $type, core\debug\IStackCall $stackCall);
    public function addStub(array $dumpObjects, core\debug\IStackCall $stackCall);

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



interface IHandler extends IGroupNode {

}

interface IWriter {
    public function writeNode(INode $node);
}