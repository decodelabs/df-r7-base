<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use df;
use df\core;
use df\flex;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}