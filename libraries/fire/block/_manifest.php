<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\aura;
use df\arch;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IBlock extends core\xml\IRootInterchange, aura\view\IDeferredRenderable {
    public function getName();
    public function getDisplayName();
    public function isNested($flag=null);
    public function getVersion();
    public function isEmpty();
    public function isHidden();
    public function getTransitionValue();
    public function setTransitionValue($value);

    public static function getOutputTypes();
    public function canOutput($outputType);
    public function getFormat();
    public function getFormDelegateName();

    public static function getDefaultCategories();
}


interface IFormDelegate extends arch\form\IDelegate, arch\form\IInlineFieldRenderableDelegate, arch\form\IResultProviderDelegate {}