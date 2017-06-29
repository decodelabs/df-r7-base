<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics;

use df;
use df\core;
use df\spur;

class Event implements IEvent {

    use core\collection\TAttributeContainer;

    protected $_category;
    protected $_name;
    protected $_label;

    public function __construct($category, string $name, $label=null, array $attributes=null) {
        $this->setCategory($category);
        $this->setName($name);
        $this->setLabel($label);
        $this->setAttributes($attributes);
    }

    public function getUniqueId() {
        return $this->_category.'/'.$this->_name;
    }

    public function setCategory($category) {
        $this->_category = $category;
        return $this;
    }

    public function getCategory() {
        return $this->_category;
    }

    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->_name;
    }

    public function setLabel($label) {
        $this->_label = $label;
        return $this;
    }

    public function getLabel() {
        return $this->_label;
    }
}
