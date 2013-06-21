<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

class Stub extends Group implements core\log\IStubNode {
    
    protected $_title = 'Stub';
    protected $_message;
    
    public function __construct($message, $file=null, $line=null) {
        $this->_message = $message;
        parent::__construct($this->_title, $file, $line);
    }
    
    public function getNodeType() {
        return 'stub';
    }
    
    public function getMessage() {
        return $this->_message;
    }
    
    public function isCritical() {
        return true;
    }
}
