<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use df;
use df\core;
use df\aura;
use df\arch;
use df\fire;
    
// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}



// Interfaces
interface IConfig extends core\IConfig {
    public function getLayoutList($area=null);
    public function getLayoutDefinition($id);
    public function isStaticLayout($id);
    public function getStaticLayoutDefinition($id);
    public function getAllLayoutDefinitions();
    public function setLayoutDefinition(IDefinition $definition);
    public function removeLayoutDefinition($id);
}

interface IDefinition {
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
    public function addSlot(fire\slot\IDefinition $slot);
    public function getSlots();
    public function getSlot($id);
    public function removeSlot($id);
    public function countSlots();
    public function setSlotOrder(array $ids);
}


interface IMap extends aura\view\ILayoutMap {
    public function getTheme();
    public function setEntries(array $entries);
    public function addEntries(array $entries);
    public function addEntry(IMapEntry $entry);
    public function getEntries();
    public function removeEntry($id);
    public function clearEntries();
}

interface IMapEntry {
    public function getId();
    public function allowsTheme(aura\theme\ITheme $theme);
    public function matches(arch\IRequest $request);
    public function apply(aura\view\ILayoutView $view);
}

interface IContent extends core\collection\IAttributeContainer, core\xml\IRootInterchange {
    public function setId($id);
    public function getId();

    public function setSlots(array $slots);
    public function addSlots(array $slots);
    public function setSlot(fire\slot\IContent $slot);
    public function getSlot($id);
    public function getSlots();
    public function hasSlot($id);
    public function removeSlot($id);
    public function clearSlots();
    public function countSlots();
}