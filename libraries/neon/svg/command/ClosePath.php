<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\command;

use df;
use df\core;
use df\neon;
    
class ClosePath extends Base implements neon\svg\IClosePathCommand {

    public function __construct() {}

    public function toString() {
        return $this->_isRelative ? 'z' : 'Z';
    }
}