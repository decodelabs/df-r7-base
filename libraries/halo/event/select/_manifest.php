<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

// Interfaces
interface IDispatcher extends halo\event\IDispatcher {
    
}

interface IHandler extends halo\event\IHandler {
    public function _exportToMap(&$map);
}
