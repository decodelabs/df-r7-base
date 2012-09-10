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
    
class SlotDefinition implements ISlotDefinition {

    protected $_id;
    protected $_name;
    protected $_isStatic = false;
    protected $_isLayoutChild = false;
    protected $_minBlocks = 0;
    protected $_maxBlocks = null;
    protected $_blockTypes = array();

    public function __construct($id=null, $name=null, $isStatic=false) {
    	$this->setId($id);
    	$this->setName($name);
    	$this->_setStatic($isStatic);
    }


// Id
    public function setId($id) {
    	if($id === null) {
    		$id = 'primary';
    	}

    	$this->_id = $id;
    	return $this;
    }

	public function getId() {
		return $this->_id;
	}

	public function isPrimary() {
		return $this->_id == 'primary';
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


// Layout child
	public function isLayoutChild() {
		return $this->_isLayoutChild;
	}

	public function _setLayoutChild($flag=true) {
		$this->_isLayoutChild = (bool)$flag;
		return $this;
	}


// Blocks
	public function setMinBlocks($minBlocks) {
		$this->_minBlocks = (int)$minBlocks;
		return $this;
	}

	public function getMinBlocks() {
		return $this->_minBlocks;
	}

	public function setMaxBlocks($maxBlocks) {
		$maxBlocks = (int)$maxBlocks;

		if($maxBlocks <= 0) {
			$maxBlocks = null;
		}

		$this->_maxBlocks = $maxBlocks;
		return $this;
	}

	public function getMaxBlocks() {
		return $this->_maxBlocks;
	}

	public function hasBlockLimit() {
		return $this->_maxBlocks !== null;
	}

	public function setBlockTypes(array $types) {
		$this->_blockTypes = $types;
		return $this;
	}

	public function getBlockTypes() {
		return $this->_blockTypes;
	}
}