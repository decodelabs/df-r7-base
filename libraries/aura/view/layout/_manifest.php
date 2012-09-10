<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\layout;

use df;
use df\core;
use df\aura;
use df\arch;
    
// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IDataContainer extends core\IAttributeContainer {

}

interface ILayoutDefinition {
	public function setId($id);
	public function getId();

	public function setName($name);
	public function getName();

	public function isStatic();
	public function _setStatic($flag=true);

	public function setAreas(array $areas);
	public function getAreas();
	public function hasArea($area);
	public function hasAreas();
	public function countAreas();

	public function setSlots(array $slots);
	public function addSlots(array $slots);
	public function addSlot(ISlotDefinition $slot);
	public function getSlots();
	public function getSlot($id);
	public function removeSlot($id);
	public function countSlots();
	public function setSlotOrder(array $ids);
}

interface ISlotDefinition {
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

	public function setBlockTypes(array $types);
	public function getBlockTypes();
}

