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
    

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IManager extends core\IManager {
    
}

interface ITask {

}