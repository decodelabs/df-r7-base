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
    
class LayoutDefinition implements ILayoutDefinition {

	protected $_id;
	protected $_name;
	protected $_isStatic = false;
	protected $_areas = array();
	protected $_slots = array();

	public function __construct($id=null, $name=null, $isStatic=false) {
		$this->setId($id);
		$this->setName($name);
		$this->_setStatic($isStatic);
	}

// Id
	public function setId($id) {
		if($id === null) {
			$id = 'Default';
		}

		$this->_id = ucfirst($id);
		return $this;
	}

	public function getId() {
		return $this->_id;
	}

// Name
	public function setName($name) {
		if($name === null) {
			$name = $this->_id;
		}

		$this->_name = $name;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

// Static
	public function isStatic() {
		return $this->_isStatic;
	}

	public function _setStatic($flag=true) {
		$this->_isStatic = (bool)$flag;
		return $this;
	}

// Areas
	public function setAreas(array $areas) {
		$this->_areas = $areas;

		foreach($this->_areas as $i => $area) {
			$this->_areas[$i] = ltrim($area, arch\Request::AREA_MARKER);
		}

		return $this;
	}

	public function getAreas() {
		return $this->_areas;
	}

	public function hasArea($area) {
		return in_array(ltrim($area, arch\Request::AREA_MARKER), $this->_areas);
	}

	public function hasAreas() {
		return !empty($this->_areas);
	}

	public function countAreas() {
		return count($this->_areas);
	}

// Slots
	public function setSlots(array $slots) {
		$this->_slots = array();
		return $this->addSlots($slots);
	}

	public function addSlots(array $slots) {
		foreach($slots as $slot) {
			if($slot instanceof ISlotDefinition) {
				$this->addSlot($slot);
			}
		}

		return $this;
	}

	public function addSlot(ISlotDefinition $slot) {
		$slot->_setLayoutChild(true);
		$this->_slots[$slot->getId()] = $slot;

		if($slot->isPrimary()) {
			$slot->_setStatic(true);
		}

		return $this;
	}

	public function getSlots() {
		return $this->_slots;
	}

	public function getSlot($id) {
		if(isset($this->_slots[$id])) {
			return $this->_slots[$id];
		}

		return null;
	}

	public function removeSlot($id) {
		unset($this->_slots[$id]);
		return $this;
	}

	public function countSlots() {
		return count($this->_slots);
	}

	public function setSlotOrder(array $ids) {
		$list = array();

		foreach($ids as $id) {
			if(isset($this->_slots[$id])) {
				$list[$id] = $this->_slots[$id];
				unset($this->_slots[$id]);
			}
		}

		foreach($this->_slots as $id => $slot) {
			$list[$id] = $slot;
		}

		$this->_slots = $list;
		return $this;
	}
}