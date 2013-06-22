<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\writer;

use df;
use df\core;
use df\halo;
    
class ChromePhp implements core\log\IWriter {

    const VERSION = '4.0.0';
    const HEADER_NAME = 'X-ChromeLogger-Data';

    protected $_buffer = array();
    protected $_request = null;
    protected $_writeBacktrace = true;

    public static function isAvailable() {
        return false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')
            || false !== strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS');
    }

    public function getId() {
        return 'ChromePhp';
    }

    public function flush(core\log\IHandler $handler) {
        $application = df\Launchpad::$application;

        if($application instanceof core\application\Http) {
            $data = base64_encode(utf8_encode(json_encode([
                'version' => self::VERSION,
                'columns' => ['log', 'backtrace', 'type'],
                'request_uri' => @$_SERVER['REQUEST_URI'],
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

    public function writeNode(core\log\IHandler $handler, core\log\INode $node) {
        switch($node->getNodeType()) {
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
            case 'deprecated':
                return $this->writeMessageNode($handler, $node);

            case 'stackTrace':
                return $this->writeStackTraceNode($handler, $node);

            case 'stub':
                return $this->writeStubNode($handler, $node);
        }
    }

    public function writeDumpNode(core\log\IHandler $handler, core\log\IDumpNode $node) {
        $inspector = new core\debug\dumper\Inspector();
        $data = $inspector->inspect($node->getObject(), $node->isDeep());

        return $this->_addRow(
            $node, $data->getDataValue()
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
        return $this->_addRow($node, $node->getNodeTitle(), 'groupEnd');
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


    protected function _addRow(core\log\INode $node, $logString, $type='log') {
        $backTrace = null;

        if($this->_writeBacktrace && substr($type, 0, 5) != 'group') {
            if(!$this->_request) {
                $application = df\Launchpad::$application;

                if($application instanceof core\application\Http
                && $application->hasContext()) {
                    $this->_request = $application->getContext()->request->toString();
                }
            }

            $request = $this->_request ? $this->_request : @$_SERVER['REQUEST_URI'];
            $backTrace = core\io\Util::stripLocationFromFilePath($node->getFile()).' : '.$node->getLine();

            if($request) {
                $backTrace .= ' ['.$request.']';
            }
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

        foreach($trace->toArray() as $call) {
            $output[] = $call->getSignature().' - '.$call->getFile().' : '.$call->getLine();
        }

        return $output;
    }
}