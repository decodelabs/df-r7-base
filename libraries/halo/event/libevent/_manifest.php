<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

// Interfaces
interface IDispatcher extends halo\event\IDispatcher {
    public function getEventBase();
}

interface IHandler extends halo\event\IHandler {
    
}