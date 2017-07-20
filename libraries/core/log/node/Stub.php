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
    protected $_isCritical = true;

    public function __construct($message, $critical=true, $file=null, $line=null) {
        $this->_message = $message;
        $this->_isCritical = $critical;
        parent::__construct($this->_title, $file, $line);
    }

    public function getNodeType(): string {
        return 'stub';
    }

    public function getMessage(): ?string {
        return $this->_message;
    }

    public function isCritical() {
        return $this->_isCritical;
    }
}
