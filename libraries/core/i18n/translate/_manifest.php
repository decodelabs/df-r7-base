<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\translate;

use df\core;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ITranslationProxy {
    public function _($phrase);
}

interface IHandler {
    public function translate(array $args);
}