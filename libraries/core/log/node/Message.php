<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

class Message implements core\log\IMessageNode {
    
    use core\debug\TLocationProvider;
    
    protected $_message;
    protected $_type;
    
    public function __construct($message, $type=core\log\IMessageNode::INFO, $file=null, $line=null) {
        $this->_message = $message;
        
        if(is_string($type)) {
            switch(strtolower($type)) {
                case 'info':
                    $type = core\log\IMessageNode::INFO;
                    break;
                    
                case 'todo':
                    $type = core\log\IMessageNode::TODO;
                    break;
                    
                case 'warning':
                    $type = core\log\IMessageNode::WARNING;
                    break;
                    
                case 'error':
                    $type = core\log\IMessageNode::ERROR;
                    break;
                    
                case 'deprecated':
                    $type = core\log\IMessageNode::DEPRECATED;
                    break;
                    
                default:
                    $type = core\log\IMessageNode::INFO;
                    break;
            }
        }
        
        switch($type) {
            case core\log\IMessageNode::INFO:
            case core\log\IMessageNode::TODO:
            case core\log\IMessageNode::WARNING:
            case core\log\IMessageNode::ERROR:
            case core\log\IMessageNode::DEPRECATED:
                break;
                
            default:
                $type = core\log\IMessageNode::INFO;
                break;
        }
        
        $this->_type = $type;
        $this->_file = $file;
        $this->_line = $line;
    }

    public function getMessage() {
        return $this->_message;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getNodeTitle() {
        switch($this->_type) {
            case core\log\IMessageNode::INFO: return 'Info';
            case core\log\IMessageNode::TODO: return 'Todo';
            case core\log\IMessageNode::WARNING: return 'Warning';
            case core\log\IMessageNode::ERROR: return 'Error';  
            case core\log\IMessageNode::DEPRECATED: return 'Deprecated';  
        }
    }
    
    public function isCritical() {
        return $this->_type === core\log\IMessageNode::ERROR;
    }
    
    public function getNodeType() {
        return lcfirst($this->getNodeTitle());
    }
}
