<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg\command;

use df;
use df\core;
use df\neon;

class HorizontalLine extends Base implements neon\vector\svg\IHorizontalLineCommand {

    protected $_x;

    public function __construct($x) {
        $this->setX($x);
    }

    public function setX($x) {
        $this->_x = core\unit\DisplaySize::factory($x, null, true);
        return $this;
    }

    public function getX() {
        return $this->_x;
    }

    public function toString(): string {
        $output = $this->_isRelative ? 'h' : 'H';
        $output .= $this->_x->toString();

        return $output;
    }
}