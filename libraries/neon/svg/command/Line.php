<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\command;

use df;
use df\core;
use df\neon;
    
class Line extends Base implements neon\svg\ILineCommand {

    protected $_x;
    protected $_y;

    public function __construct($x, $y) {
        $this->setPosition($x, $y);
    }

    public function setPosition($x, $y) {
        return $this->setX($x)->setY($y);
    }

    public function setX($x) {
        $this->_x = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getX() {
        return $this->_x;
    }

    public function setY($y) {
        $this->_y = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getY() {
        return $this->_y;
    }

    public function toString() {
        $output = $this->_isRelative ? 'l' : 'L';
        $output .= $this->_x->toString().' ';
        $output .= $this->_y->toString();

        return $output;
    }
}