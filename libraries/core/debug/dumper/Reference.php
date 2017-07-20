<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Reference implements IReferenceNode {

    use core\TStringProvider;
    use TNode;

    protected $_type;
    protected $_dumpId;

    public function __construct(IInspector $inspector, string $type, $dumpId) {
        $this->_inspector = $inspector;
        $this->_type = $type;
        $this->_dumpId = $dumpId;
    }

    public function getType(): string {
        return $this->_type;
    }

    public function isArray(): bool {
        return $this->_type == 'array';
    }

    public function getDumpId(): string {
        return $this->_dumpId;
    }

    public function getDataValue() {
        return $this->toString();
    }

    public function toString(): string {
        return $this->_type.'(&'.$this->_dumpId.')';
    }
}
