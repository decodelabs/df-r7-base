<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\writer;

use df;
use df\core;
use df\halo;

class FirePhp implements core\log\IWriter {

    use core\log\TWriter;
    use core\log\THttpWriter;

    const PROTOCOL_URL = 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2';
    const PLUGIN_URL = 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.4';
    const DUMP_STRUCTURE_URL = 'http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1';
    const CONSOLE_STRUCTURE_URL = 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1';

    const CHUNK_SIZE = 5000;

    protected static $_messageIndex = 0;
    protected $_buffer = array();

    public static function isAvailable(halo\protocol\http\IRequestHeaderCollection $headers) {
        if(preg_match_all('/\sFirePHP\/([\.\d]*)\s?/si', $headers->get('user-agent'), $matches) 
        && version_compare($matches[1][0],'0.0.6','>=')) {
            return true;
        } else if(preg_match_all('/^([\.\d]*)$/si', $headers->get('X-FirePHP-Version'), $matches) 
        && version_compare($matches[1][0],'0.0.6','>=')) {
            return true;
        }

        return false;
    }

    public function __construct() {
        $this->reset();
    }

    public function getId() {
        return 'FirePhp';
    }

    public function reset() {
        $this->_buffer = [
            'X-Wf-Protocol-1' => self::PROTOCOL_URL,
            'X-Wf-1-Plugin-1' => self::PLUGIN_URL,
            'X-Wf-1-Structure-1' => self::CONSOLE_STRUCTURE_URL
        ];

        return $this;
    }

    public function flush(core\log\IHandler $handler) {
        $application = df\Launchpad::$application;

        if($application instanceof core\application\Http) {
            $this->_buffer['X-Wf-1-Index'] = self::$_messageIndex;
            $augmentor = $application->getResponseAugmentor();

            foreach($this->_buffer as $key => $value) {
                $augmentor->setHeaderForCurrentRequest($key, $value);
            }
        }

        return $this->reset();
    }

    public function writeContextNode(core\log\IHandler $handler, core\debug\IContext $node) {
        $renderer = new core\debug\renderer\PlainText($handler);

        $stats = $renderer->getStats();
        $this->_addRow('GROUP_START', 'Stats - '.$stats['Time'], null, null, null, ['Collapsed' => 'true']);
        
        foreach($stats as $key => $value) {
            $this->_addRow('INFO', $key, $value);
        }

        $this->_addRow('GROUP_END');


        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
        }

        return $this;
    }

    public function writeDumpNode(core\log\IHandler $handler, core\log\IDumpNode $node) {
        $inspector = new core\debug\dumper\Inspector();
        $data = $inspector->inspect($node->getObject(), $node->isDeep());

        return $this->_addRow(
            'DUMP', 
            $node->getNodeTitle(), 
            $this->_convertDumperNode($inspector, $data)
        );
    }

    public function writeExceptionNode(core\log\IHandler $handler, core\log\IExceptionNode $node) {
        return $this->_addRow(
            'EXCEPTION',
            'Exception',
            [
                'Class' => $node->getExceptionClass(),
                'Message' => $node->getMessage(),
                'File' => $node->getFile(),
                'Line' => $node->getLine(),
                'Type' => 'throw',
                'Trace' => $node->getException()->getTrace()
            ],
            $node->getFile(),
            $node->getLine()
        );
    }

    public function writeGroupNode(core\log\IHandler $handler, core\log\IGroupNode $node) {
        $this->_addRow('GROUP_START', $node->getNodeTitle()); //, null, null, null, ['Collapsed' => 'true']);

        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
        }

        return $this->_addRow('GROUP_END');
    }

    public function writeMessageNode(core\log\IHandler $handler, core\log\IMessageNode $node) {
        $message = $node->getMessage();
        $label = null;

        switch($type = $node->getNodeType()) {
            case 'warning':
                $type = 'WARN';
                break;

            case 'deprecated':
                $type = 'WARN';
                $label = 'DEPRECATED';
                break;

            case 'todo':
                $type = 'WARN';
                $label = 'TODO';
                break;

            case 'error':
                $type = 'ERROR';
                break;

            case 'info':
            default:
                $type = 'INFO';
                break;
        }

        return $this->_addRow(
            $type, $label, $message, $node->getFile(), $node->getLine()
        );
    }

    public function writeStackTraceNode(core\log\IHandler $handler, core\debug\IStackTrace $node) {
        $call = $node->getFirstCall();

        return $this->_addRow(
            'TRACE',
            null,
            [
                'Class' => $call->getClassName(),
                'Type' => $call->getTypeString(),
                'Function' => $call->getFunctionName(),
                'Message' => $call->getSignature(),
                'File' => $call->getFile(),
                'Line' => $call->getLine(),
                'Args' => $call->getArgs(),
                'Trace' => $node->toArray()
            ]
        );
    }

    public function writeStubNode(core\log\IHandler $handler, core\log\IStubNode $node) {
        $this->_addRow(
            'GROUP_START', 
            'STUB: '.$node->getMessage(), 
            null, 
            $node->getFile(), 
            $node->getLine(), 
            ['Color' => 'red']
        );

        foreach($node->getChildren() as $child) {
            $this->writeNode($handler, $child);
        }

        return $this->_addRow('GROUP_END');
    }

    protected function _addRow($type, $label=null, $body=null, $file=null, $line=null, array $meta=array()) {
        $meta['Type'] = $type;

        if($label !== null) {
            $meta['Label'] = $label;
        }

        if($file !== null) {
            $meta['File'] = $file;
        }

        if($line !== null) {
            $meta['Line'] = $line;
        }

        $message = json_encode([$meta, $body]);
        $parts = explode("\n", chunk_split($message, self::CHUNK_SIZE, "\n"));
        $count = count($parts);

        for($i = 0; $i < $count; $i++) {
            if($part = $parts[$i]) {
                if($count > 2) {
                    $chunkData = ($i == 0 ? strlen($message) : '').'|'.$part.'|'.($i < $count - 2 ? '\\' : '');
                } else {
                    $chunkData = strlen($part).'|'.$part.'|';
                }
                
                $this->_buffer['X-Wf-1-1-1-'.++self::$_messageIndex] = $chunkData;
            }
        }

        return $this;
    }

    protected function _convertDumperNode(core\debug\dumper\IInspector $inspector, core\debug\dumper\INode $data) {
        if($data instanceof core\debug\dumper\IStructureNode) {
            $output = [];

            if(!$isArray = $data->isArray()) {
                $output['__className'] = $data->getType();
            }

            foreach($data->getProperties() as $property) {
                $name = $property->getName();

                if(!$isArray) {
                    if(empty($name)) {
                        $name = '#';
                    }

                    $name = $property->getVisibilityString().':'.$name;
                }
                
                $value = $property->getValue();
                $output[$name] = $this->_convertDumperNode($inspector, $inspector->inspect($value));
                unset($value);
            }

            return $output;
        } else {
            return $data->getDataValue($inspector);
        }
    }
}