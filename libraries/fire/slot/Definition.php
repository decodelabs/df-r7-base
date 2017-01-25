<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\slot;

use df;
use df\core;
use df\fire;
use df\aura;
use df\arch;

class Definition implements IDefinition {

    protected $_id;
    protected $_name;
    protected $_isStatic = false;
    protected $_isLayoutChild = false;
    protected $_minBlocks = 0;
    protected $_maxBlocks = null;
    protected $_category;



// Interchange
    public static function fromArray(array $values) {
        $output = new self(@$values['id'], @$values['name'], @$values['static']);
        $output->_isLayoutChild = @$values['layoutChild'];
        $output->_minBlocks = @$values['minBlocks'];
        $output->_maxBlocks = @$values['maxBlocks'];
        $output->_category = @$values['category'];
    }

    public function toArray(): array {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'static' => $this->_isStatic,
            'layoutChild' => $this->_isLayoutChild,
            'minBlocks' => $this->_minBlocks,
            'maxBlocks' => $this->_maxBlocks,
            'category' => $this->_category
        ];
    }

    public static function createDefault() {
        return new self('default', 'Default');
    }

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
        $this->_isStatic = $flag;
        return $this;
    }


// Layout child
    public function isLayoutChild() {
        return $this->_isLayoutChild;
    }

    public function _setLayoutChild($flag=true) {
        $this->_isLayoutChild = $flag;
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

    public function setCategory($category) {
        if($category !== null) {
            $category = fire\category\Base::factory($category);
            $this->_category = $category->getName();
        } else {
            $this->_category = null;
        }

        return $this;
    }

    public function getCategory() {
        return $this->_category;
    }
}