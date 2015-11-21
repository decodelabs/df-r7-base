<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\decorator;

use df;
use df\core;
use df\arch;
use df\aura;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IDecorator extends aura\view\ICascadingHelperProvider {

}


interface IFormDecorator extends IDecorator, arch\node\IForm {
    public function renderUi();
}

interface IDelegateDecorator extends IDecorator, arch\node\IForm {
    public function renderUi();
}