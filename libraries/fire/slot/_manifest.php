<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\slot;

use df;
use df\core;
use df\fire;
use df\flex;
use df\aura;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IDefinition extends core\IArrayInterchange {
    public function setId($id);
    public function getId();
    public function isPrimary();

    public function setName($name);
    public function getName();

    public function isStatic();
    public function _setStatic($flag=true);

    public function isLayoutChild();
    public function _setLayoutChild($flag=true);

    public function setMinBlocks($minBlocks);
    public function getMinBlocks();
    public function setMaxBlocks($maxBlocks);
    public function getMaxBlocks();
    public function hasBlockLimit();

    public function setCategory($category);
    public function getCategory();
}


interface IContent extends core\collection\IAttributeContainer, flex\xml\IRootInterchange, aura\view\IDeferredRenderable {

    public function setId($id);
    public function getId();
    public function isPrimary();

    public function isNested(bool $flag=null);
    public function hasChanged(bool $flag=null);

    public function setBlocks(array $blocks);
    public function addBlocks(array $blocks);
    public function setBlock($index, fire\block\IBlock $block);
    public function putBlock($index, fire\block\IBlock $block);
    public function addBlock(fire\block\IBlock $block);
    public function getBlock($index);
    public function getBlocks();
    public function hasBlock($index);
    public function removeBlock($index);
    public function clearBlocks();
    public function countBlocks();
}