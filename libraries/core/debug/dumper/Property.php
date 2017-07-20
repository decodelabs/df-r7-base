<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Property {

    protected $_name = null;
    protected $_value;
    protected $_visibility = IProperty::VIS_PUBLIC;
    protected $_deep = false;

    public function __construct(?string $name, $value, string $visibility='public', bool $deep=false) {
        $this->setName($name);
        $this->_value = $value;
        $this->setVisibility($visibility);
        $this->_deep = $deep;
    }

// Name
    public function setName(?string $name) {
        if(!strlen($name)) {
            $name = null;
        }

        $this->_name = $name;
        return $this;
    }

    public function hasName(): bool {
        return $this->_name !== null;
    }

    public function getName(): ?string {
        return $this->_name;
    }


// Value
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }

    public function getValue() {
        return $this->_value;
    }

    public function inspectValue(IInspector $inspector): INode {
        if($this->_value instanceof INode) {
            return $this->_value;
        }

        return $inspector->inspect($this->_value, $this->_deep);
    }


// Visibility
    public function setVisibility(string $visibility) {
        switch($visibility) {
            case 'private':
            case 'protected':
                $this->_visibility = $visibility;
                break;

            case 'public':
            default:
                $this->_visibility = 'public';
                break;
        }

        return $this;
    }

    public function getVisibility(): string {
        return $this->_visibility;
    }

    public function isPublic(): bool {
        return $this->_visibility === IProperty::VIS_PUBLIC;
    }

    public function isProtected(): bool {
        return $this->_visibility === IProperty::VIS_PROTECTED;
    }

    public function isPrivate(): bool {
        return $this->_visibility === IProperty::VIS_PRIVATE;
    }

// Deep
    public function isDeep(): bool {
        return $this->_deep;
    }

    public function canInline(): bool {
        return !$this->_deep
            && !$this->hasName()
            && (is_scalar($this->_value) || is_null($this->_value))
            && false === strpos($this->_value, "\n");
    }
}
