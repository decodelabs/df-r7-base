<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\bucket;

use df;
use df\core;
use df\neon;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IBucket extends core\io\IAcceptTypeProcessor {
    public function getName();
    public function getDisplayName();

    public function isUserSpecific();
    public function allowOnePerUser();
}
