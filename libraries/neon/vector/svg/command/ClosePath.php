<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg\command;

use df;
use df\core;
use df\neon;

class ClosePath extends Base implements neon\vector\svg\IClosePathCommand {

    public function __construct() {}

    public function toString(): string {
        return $this->_isRelative ? 'z' : 'Z';
    }
}