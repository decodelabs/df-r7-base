<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class AppId implements neon\vector\dxf\IAppIdTable {

    use neon\vector\dxf\TTable;

    public function getType() {
        return 'APPID';
    }

    public function toString(): string {
        return $this->_writeBaseString('');
    }
}