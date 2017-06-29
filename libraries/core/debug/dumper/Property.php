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

    public function __construct($name, $value, $visibility=IProperty::VIS_PUBLIC, $deep=false) {
        $this->setName($name);
        $this->_value = $value;
        $this->setVisibility($visibility);
        $this->_deep = (bool)$deep;
    }

// Name
    public function setName($name) {
        $this->_name = (string)$name;

        if(empty($this->_name) && $this->_name !== '0') {
            $this->_name = null;
        }

        return $this;
    }

    public function hasName() {
        return $this->_name !== null;
    }

    public function getName(): string {
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

    public function inspectValue(IInspector $inspector) {
        if($this->_value instanceof core\debug\IDump) {
            return $this->_value;
        }

        return $inspector->inspect($this->_value, $this->_deep);
    }


// Visibility
    public function setVisibility($visibility) {
        df\Launchpad::loadBaseClass('core/collection/_manifest');
        df\Launchpad::loadBaseClass('core/collection/Util');

        $this->_visibility = core\collection\Util::normalizeEnumValue(
            $visibility,
            [
                'public' => IProperty::VIS_PUBLIC,
                'protected' => IProperty::VIS_PROTECTED,
                'private' => IProperty::VIS_PRIVATE
            ],
            IProperty::VIS_PUBLIC
        );

        return $this;
    }

    public function getVisibility() {
        return $this->_visibility;
    }

    public function getVisibilityString() {
        switch($this->_visibility) {
            case IProperty::VIS_PRIVATE:
                return 'private';

            case IProperty::VIS_PROTECTED:
                return 'protected';

            case IProperty::VIS_PUBLIC:
                return 'public';
        }
    }

    public function isPublic() {
        return $this->_visibility === IProperty::VIS_PUBLIC;
    }

    public function isProtected() {
        return $this->_visibility === IProperty::VIS_PROTECTED;
    }

    public function isPrivate() {
        return $this->_visibility === IProperty::VIS_PRIVATE;
    }

// Deep
    public function isDeep() {
        return $this->_deep;
    }

    public function canInline() {
        return !$this->_deep
            && !$this->hasName()
            && (is_scalar($this->_value) || is_null($this->_value))
            && false === strpos($this->_value, "\n");
    }
}
