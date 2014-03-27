<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\router;

use df;
use df\core;
use df\arch;
    
abstract class Base implements arch\IRouter {

    public function newRequest($input) {
        return arch\Request::factory($input);
    }
}