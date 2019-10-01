<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log;

use df;
use df\core;

// Exceptions
interface IException
{
}



// Interfaces
interface IManager extends core\IManager
{
    public function logAccessError($code=403, $request=null, $message=null);
    public function logNotFound($request=null, $message=null);
    public function logException(\Throwable $exception, $request=null);

    public function swallow($block, ...$args);
}



interface IMessageProvider
{
    public function getMessage(): ?string;
}

interface IEntryPoint
{
    public function addNode(INode $node);
    public function newGroup($title=null, $file=null, $line=null);

    public function exception(\Throwable $exception);
    public function addException(\Throwable $exception);

    public function info($message);
    public function todo($message);
    public function warning($message);
    public function error($message);
    public function addMessage($message, $type, core\debug\IStackCall $stackCall);

    public function stackTrace(string $message=null, int $rewind=0);
}

trait TEntryPoint
{

// Entry point
    public function newGroup($title=null, $file=null, $line=null)
    {
        return new core\log\node\Group($title, $file, $line);
    }


    // Exception
    public function exception(\Throwable $exception)
    {
        return $this->addException($exception);
    }

    public function addException(\Throwable $exception)
    {
        df\Launchpad::loadBaseClass('core/log/node/Exception');
        $this->addNode(new core\log\node\Exception($exception));

        return $this;
    }


    // Messages
    public function info($message)
    {
        return $this->addMessage($message, IMessageNode::INFO, core\debug\StackCall::factory(1));
    }

    public function todo($message)
    {
        return $this->addMessage($message, IMessageNode::TODO, core\debug\StackCall::factory(1));
    }

    public function warning($message)
    {
        return $this->addMessage($message, IMessageNode::WARNING, core\debug\StackCall::factory(1));
    }

    public function error($message)
    {
        return $this->addMessage($message, IMessageNode::ERROR, core\debug\StackCall::factory(1));
    }

    public function addMessage($message, $type, core\debug\IStackCall $stackCall)
    {
        df\Launchpad::loadBaseClass('core/log/node/Message');
        $this->addNode(new core\log\node\Message($message, $type, $stackCall->getFile(), $stackCall->getLine()));

        return $this;
    }


    public function stackTrace(string $message=null, int $rewind=0)
    {
        df\Launchpad::loadBaseClass('core/debug/StackTrace');
        df\Launchpad::loadBaseClass('core/debug/StackCall');

        $this->addNode(
            core\debug\StackTrace::factory($rewind + 1)
                ->setMessage($message)
        );

        return $this;
    }
}

interface ILocationProvider
{
    public function getFile(): ?string;
    public function getLine(): ?int;
}

interface INode extends ILocationProvider
{
    public function getNodeTitle();
    public function getNodeType(): string;
    public function isCritical();
}

interface IInspectableNode
{
    public function inspect(): core\debug\dumper\INode;
}

interface IGroupNode extends INode, IEntryPoint, core\IArrayProvider
{
    public function setNodeTitle($title);
    public function addChild(INode $node);
    public function getChildren();
    public function hasChildren();
    public function clearChildren();
    public function getNodeCounts();
}

interface IMessageNode extends INode, IMessageProvider
{
    const INFO = 1;
    const TODO = 2;
    const WARNING = 3;
    const ERROR = 4;

    public function getType();
}

interface IDumpNode extends INode, IInspectableNode
{
    public function getObject();
    public function isDeep(): bool;
}

interface IExceptionNode extends INode, IInspectableNode
{
    public function getException(): \Throwable;
    public function getExceptionClass();
    public function getCode();
    public function getMessage(): ?string;
    public function getStackTrace(): core\debug\IStackTrace;
    public function getStackCall(): core\debug\IStackCall;
}

interface IStubNode extends IGroupNode, IMessageProvider
{
}


interface IWriterProvider
{
    public function addWriter(IWriter $writer);
    public function removeWriter(IWriter $writer);
    public function getWriters();
}

interface IHandler extends IEntryPoint, IWriterProvider
{
    public function flush();
}

trait TWriterProvider
{
    protected $_writers = [];

    public function addWriter(IWriter $writer)
    {
        $this->_writers[$writer->getId()] = $writer;
        return $this;
    }

    public function removeWriter(IWriter $writer)
    {
        unset($this->_writers[$writer->getId()]);
        return $this;
    }

    public function getWriters()
    {
        return $this->_writers;
    }
}


interface IWriter
{
    public function getId(): string;
    public function flush(core\log\IHandler $handler);
    public function writeNode(IHandler $handler, INode $node);
    public function writeContextNode(core\log\IHandler $handler, core\debug\IContext $node);
    public function writeDumpNode(core\log\IHandler $handler, core\log\IDumpNode $node);
    public function writeExceptionNode(core\log\IHandler $handler, core\log\IExceptionNode $node);
    public function writeGroupNode(core\log\IHandler $handler, core\log\IGroupNode $node);
    public function writeMessageNode(core\log\IHandler $handler, core\log\IMessageNode $node);
    public function writeStackTraceNode(core\log\IHandler $handler, core\debug\IStackTrace $node);
    public function writeStubNode(core\log\IHandler $handler, core\log\IStubNode $node);
}


trait TWriter
{
    public function getId(): string
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function writeNode(core\log\IHandler $handler, core\log\INode $node)
    {
        switch ($node->getNodeType()) {
            case 'context':
                return $this->writeContextNode($handler, $node);

            case 'dump':
                return $this->writeDumpNode($handler, $node);

            case 'exception':
                return $this->writeExceptionNode($handler, $node);

            case 'group':
                return $this->writeGroupNode($handler, $node);

            case 'info':
            case 'todo':
            case 'warning':
            case 'error':
                return $this->writeMessageNode($handler, $node);

            case 'stackTrace':
                return $this->writeStackTraceNode($handler, $node);

            case 'stub':
                return $this->writeStubNode($handler, $node);
        }
    }
}

trait THttpWriter
{
    protected $_request = null;

    protected function _getRequest()
    {
        if (!$this->_request) {
            $runner = df\Launchpad::$runner;

            if ($runner instanceof core\app\runner\Http
            && $runner->hasContext()) {
                $this->_request = $runner->getHttpRequest()->getUrl()->toString();
            }
        }

        return $this->_request ? $this->_request : @$_SERVER['REQUEST_URI'];
    }
}

df\Launchpad::loadBaseClass('core/debug/_manifest');
