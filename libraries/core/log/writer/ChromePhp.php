<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\writer;

use df;
use df\core;
use df\link;
    
class ChromePhp implements core\log\IWriter {

    use core\log\TWriter;
    use core\log\THttpWriter;

    const VERSION = '4.0.0';
    const HEADER_NAME = 'X-ChromeLogger-Data';

    protected $_buffer = array();
    protected $_writeBacktrace = true;

    public static function isAvailable(link\http\IRequestHeaderCollection $headers) {
        $agent = $headers->get('user-agent');

        return false !== strpos($agent, 'Chrome')
            || false !== strpos($agent, 'CriOS');
    }

    public function flush(core\log\IHandler $handler) {
        $application = df\Launchpad::$application;

        if($application instanceof core\application\Http) {
            $data = base64_encode(utf8_encode(json_encode([
                'version' => self::VERSION,
                'columns' => ['log', 'backtrace', 'type'],
                'request_uri' => $this->_getRequest(),
                'rows' => $this->_buffer
            ])));

            $application->getResponseAugmentor()->setHeaderForCurrentRequest(
                self::HEADER_NAME, $data
            );
        }

        $this->_buffer = array();
        $this->_request = null;

        return $this;
    }

    public function writeContextNode(core\log\IHandler $handler, core\debug\IContext $node) {
        $this->_addRow($node, $this->_getRequest(), 'group');
        $renderer = new core\debug\renderer\PlainText($handler);
        
        $this->_addRow(
            null,
            $renderer->renderStats(),
            'info'
        );

        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
            $this->_writeBacktrace = false;
        }

        $this->_writeBacktrace = true;
        return $this->_addRow($node, null, 'groupEnd');
    }

    public function writeDumpNode(core\log\IHandler $handler, core\log\IDumpNode $node) {
        $inspector = new core\debug\dumper\Inspector();
        $data = $inspector->inspect($node->getObject(), $node->isDeep());

        return $this->_addRow(
            $node, $data->getDataValue($inspector)
        );
    }

    public function writeExceptionNode(core\log\IHandler $handler, core\log\IExceptionNode $node) {
        $message = 'EXCEPTION';

        if($code = $node->getCode()) {
            $message .= ' '.$code;
        }
        
        $message .= ': '.$node->getMessage();
        $this->_addRow($node, $message, 'groupCollapsed');

        $this->_addRow(
            $node, 
            [
                'message' => $node->getMessage(),
                'code' => $node->getCode(),
                'class' => $node->getExceptionClass(),
                'trace' => $this->_convertStackTrace($node->getStackTrace())
            ], 
            'error'
        );

        return $this->_addRow($node, null, 'groupEnd');
    }

    public function writeGroupNode(core\log\IHandler $handler, core\log\IGroupNode $node) {
        $this->_addRow($node, $node->getNodeTitle(), 'group');

        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
            $this->_writeBacktrace = false;
        }

        $this->_writeBacktrace = true;
        return $this->_addRow($node, null, 'groupEnd');
    }

    public function writeMessageNode(core\log\IHandler $handler, core\log\IMessageNode $node) {
        $message = $node->getMessage();

        switch($type = $node->getNodeType()) {
            case 'warning':
                $type = 'warn';
                break;

            case 'deprecated':
                $type = 'warn';
                $message = 'DEPRECATED: '.$message;
                break;

            case 'todo':
                $type = 'warn';
                $message = 'TODO: '.$message;
                break;

            case 'info':
            case 'error':
            default:
                break;
        }

        return $this->_addRow($node, $message, $type);
    }

    public function writeStackTraceNode(core\log\IHandler $handler, core\debug\IStackTrace $node) {
        return $this->_addRow(
            $node, 
            $this->_convertStackTrace($node)
        );
    }

    public function writeStubNode(core\log\IHandler $handler, core\log\IStubNode $node) {
        $this->_addRow($node, 'STUB: '.$node->getMessage(), 'group');

        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
            $this->_writeBacktrace = false;
        }

        $this->_writeBacktrace = true;
        return $this->_addRow($node, $node->getNodeTitle(), 'groupEnd');
    }


    protected function _addRow(core\log\INode $node=null, $logString, $type='log') {
        $backTrace = null;

        if($this->_writeBacktrace && $node && substr($type, 0, 5) != 'group') {
            $backTrace = core\io\Util::stripLocationFromFilePath($node->getFile()).' : '.$node->getLine();
        }

        $this->_buffer[] = [
            [$logString],
            $backTrace,
            $type
        ];

        return $this;
    }

    protected function _convertStackTrace(core\debug\IStackTrace $trace) {
        $output = array();

        foreach($trace->getCalls() as $call) {
            $output[] = $call->getSignature().' - '.$call->getFile().' : '.$call->getLine();
        }

        return $output;
    }
}