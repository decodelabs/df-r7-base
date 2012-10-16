<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\ctrl\task;

use df;
use df\core;
use df\ctrl;
use df\halo;
    
class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://task';
}