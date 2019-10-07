<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\node;

use df;
use df\core;
use df\spur;

// Exceptions
interface IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}


// Interfaces
interface IBridge
{
    public function find($name);
    public function npmInstall(string $name, core\io\IMultiplexer $multiplexer=null);
    public function execute($path, $data);
    public function evaluate($js, $data=null);
}
