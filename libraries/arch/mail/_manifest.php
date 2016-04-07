<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\mail;

use df;
use df\core;
use df\arch;
use df\aura;
use df\flow;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IMail extends aura\view\IView, flow\mail\IMessage {
    public function getName();
    public function getDescription();
    public function preparePreview();
}
