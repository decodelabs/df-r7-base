<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg\command;

use df;
use df\core;
use df\neon;

class VerticalLine extends Base implements neon\vector\svg\IVerticalLineCommand {

    protected $_y;

    public function __construct($y) {
        $this->setY($y);
    }

    public function setY($y) {
        $this->_y = core\unit\DisplaySize::factory($y, null, true);
        return $this;
    }

    public function getY() {
        return $this->_y;
    }

    public function toString(): string {
        $output = $this->_isRelative ? 'v' : 'V';
        $output .= $this->_y->toString();

        return $output;
    }
}