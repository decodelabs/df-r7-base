<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css\sass;

use df;
use df\core;
use df\aura;
use df\arch;
use df\link;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IBridge {
    public function getHttpResponse();
    public function getCompiledPath();
    public function compile();
}
