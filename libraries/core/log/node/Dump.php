<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/dumper/Inspector');

class Dump implements core\log\IDumpNode {

    use core\debug\TLocationProvider;

    private static $_counter = 0;

    protected $_object;
    protected $_id;
    protected $_isDeep = false;
    protected $_isCritical = true;
    protected $_inspectData;

    public function __construct($object, bool $deep=false, bool $critical=true, string $file=null, int $line=null) {
        $this->_object = $object;
        $this->_id = ++self::$_counter;
        $this->_isDeep = (bool)$deep;
        $this->_isCritical = $critical;

        $this->_file = $file;
        $this->_line = $line;

        if(is_object($this->_object)) {
            $this->inspect();
        }
    }

    public function getNodeTitle() {
        return 'Dump #'.$this->_id.' '.ucfirst(getType($this->_object));
    }

    public function getNodeType(): string {
        return 'dump';
    }

    public function isCritical() {
        return $this->_isCritical;
    }

    public function getObject() {
        return $this->_object;
    }

    public function isDeep(): bool {
        return $this->_isDeep;
    }

    public function inspect(): core\debug\dumper\INode {
        if(!$this->_inspectData) {
            $inspector = new core\debug\dumper\Inspector();
            $this->_inspectData = $inspector->inspect($this->_object, $this->_isDeep);
        }

        return $this->_inspectData;
    }
}
