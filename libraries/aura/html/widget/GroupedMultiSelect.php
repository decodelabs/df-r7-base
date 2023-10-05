<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\core;

class GroupedMultiSelect extends GroupedSelect implements IMultiSelectWidget
{
    public const PRIMARY_TAG = 'select.multi.grouped';
    public const ARRAY_INPUT = true;

    protected $_size;
    protected $_value = [];
    protected $_noSelectionLabel = null;

    public function setNoSelectionLabel($label)
    {
        return $this;
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $tag->setAttribute('multiple', 'multiple');

        if ($this->_size !== null) {
            $tag->setAttribute('size', (int)$this->_size);
        }

        return parent::_render();
    }

    protected function _checkSelected($value, &$selectionFound)
    {
        return $this->_value->contains($value);
    }

    public function setValue($value)
    {
        if (!$value instanceof core\collection\IInputTree) {
            if ($value instanceof core\collection\ICollection) {
                $value = $value->toArray();
            }

            if ($value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }

            if (!is_array($value)) {
                $value = [$value];
            }

            $newValue = [];

            foreach ($value as $val) {
                $val = (string)$val;

                if (!strlen($val)) {
                    continue;
                }

                $newValue[] = $val;
            }

            $newValue = array_unique($newValue);
            $value = new core\collection\InputTree($newValue);
        }

        $this->_value = $value;
        return $this;
    }

    public function getValueString()
    {
        return implode(', ', $this->getValue()->toArray());
    }

    // Size
    public function setSize($size)
    {
        if ($size === true) {
            $size = 0;

            foreach ($this->_groupOptions as $set) {
                $size += count($set);
            }

            if ($size < 5) {
                $size = 5;
            } elseif ($size > 50) {
                $size = 50;
            }
        }

        $this->_size = $size;
        return $this;
    }

    public function getSize()
    {
        return $this->_size;
    }
}
