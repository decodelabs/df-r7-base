<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\node;

use df;
use df\core;

class Message implements core\debug\IMessageNode {
    
    use core\debug\TLocationProvider;
    
    const INFO = 1;
    const TODO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const DEPRECATED = 5;
    
    protected $_message;
    protected $_type;
    
    public function __construct($message, $type=self::INFO, $file=null, $line=null) {
        $this->_message = $message;
        
        if(is_string($type)) {
            switch(strtolower($type)) {
                case 'info':
                    $type = self::INFO;
                    break;
                    
                case 'todo':
                    $type = self::TODO;
                    break;
                    
                case 'warning':
                    $type = self::WARNING;
                    break;
                    
                case 'error':
                    $type = self::ERROR;
                    break;
                    
                case 'deprecated':
                    $type = self::DEPRECATED;
                    break;
                    
                default:
                    $type = self::INFO;
                    break;
            }
        }
        
        switch($type) {
            case self::INFO:
            case self::TODO:
            case self::WARNING:
            case self::ERROR:
            case self::DEPRECATED:
                break;
                
            default:
                $type = self::INFO;
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
            case self::INFO: return 'Info';
            case self::TODO: return 'Todo';
            case self::WARNING: return 'Warning';
            case self::ERROR: return 'Error';  
            case self::DEPRECATED: return 'Deprecated';  
        }
    }
    
    public function isCritical() {
        return $this->_type === self::ERROR;
    }
    
    public function getNodeType() {
        return lcfirst($this->getNodeTitle());
    }
}
