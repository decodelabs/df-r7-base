<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\style;

use df;
use df\core;
use df\aura;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ISize extends core\IStringProvider, core\IValueContainer {

	public function setUnit($unit);
	public function getUnit();
}