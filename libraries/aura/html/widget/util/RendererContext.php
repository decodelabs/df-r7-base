<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget\util;

use df;
use df\core;
use df\aura;
use df\arch;

class RendererContext implements aura\html\widget\IRendererContext {

    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_Constructor;
    use core\collection\TArrayCollection_AssociativeValueMap;

    public $cellTag;
    public $fieldTag;
    public $rowTag;
    public $key;
    public $field;
    public $counter = -1;
    public $component;
    public $divider;

    protected $_store = [];
    protected $_widget;
    protected $_rowProcessor;
    protected $_nullToNa = true;
    protected $_skipRow = false;
    protected $_skipCells = 0;

    public function __construct(aura\html\widget\IWidget $widget) {
        $this->_widget = $widget;

        if($widget instanceof aura\html\widget\IMappedListWidget) {
            $this->_rowProcessor = $widget->getRowProcessor();
        }
    }

    public function setComponent(?arch\IComponent $component) {
        $this->component = $component;
        return $this;
    }

    public function getComponent(): ?arch\IComponent {
        return $this->component;
    }

    public function getWidget(): aura\html\widget\IWidget {
        return $this->_widget;
    }

    public function getKey() {
        return $this->key;
    }

    public function getField() {
        return $this->field;
    }

    public function getCounter(): int {
        return $this->counter;
    }

    public function getCellTag(): ?aura\html\ITag {
        return $this->cellTag;
    }

    public function getRowTag(): ?aura\html\ITag {
        return $this->rowTag;
    }

    public function getFieldTag(): ?aura\html\ITag {
        return $this->fieldTag;
    }

    public function addDivider() {
        if($this->divider === null) {
            $this->divider = '';
        }

        return $this;
    }

    public function setDivider(?string $label) {
        $this->divider = $label;
        return $this;
    }

    public function getDivider(): ?string {
        return $this->divider;
    }

    public function prepareRow($row) {
        if($this->_rowProcessor) {
            $row = core\lang\Callback::call($this->_rowProcessor, $row);
        }

        return $row;
    }

    public function shouldConvertNullToNa(bool $flag=null) {
        if($flag !== null) {
            $this->_nullToNa = $flag;
            return $this;
        }

        return $this->_nullToNa;
    }

    public function reset() {
        $this->counter = 0;
        $this->clear();
        $this->key = $this->cellTag = $this->rowTag = $this->fieldTag = null;
        $this->_skipRow = false;
        $this->_skipCells = 0;

        return $this;
    }

    public function iterate($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null, aura\html\ITag $fieldTag=null) {
        $this->counter++;
        $this->clear();

        $this->_store = [];
        $this->key = $key;
        $this->cellTag = $cellTag;
        $this->rowTag = $rowTag;
        $this->fieldTag = $fieldTag;
        $this->_skipRow = false;
        $this->_skipCells = 0;
        $this->divider = null;

        return $this;
    }

    public function iterateRow($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null, aura\html\ITag $fieldTag=null) {
        $this->counter++;

        $this->key = $key;
        $this->cellTag = $cellTag;
        $this->rowTag = $rowTag;
        $this->fieldTag = $fieldTag;
        $this->_skipRow = false;
        $this->_skipCells = 0;
        $this->divider = null;

        return $this;
    }

    public function iterateField($field, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null, aura\html\ITag $fieldTag=null) {
        $this->field = $field;
        $this->cellTag = $cellTag;
        $this->rowTag = $rowTag;
        $this->fieldTag = $fieldTag;

        if($this->_skipCells) {
            $this->_skipCells--;
        }

        return $this;
    }

    public function renderCell($value, $renderer=null) {
        if($renderer) {
            try {
                $value = core\lang\Callback($renderer, $value, $this);
            } catch(\Throwable $e) {
                if(!df\Launchpad::isTesting()) {
                    $value = new aura\html\ElementString('<span class="error">ERROR: '.$e->getMessage().'</span>');
                } else {
                    throw $e;
                }
            }
        }

        if($value instanceof core\IDescribable) {
            $value = $value->getOutputDescription();
        }

        if($value instanceof aura\html\IRenderable
        || $value instanceof aura\view\IDeferredRenderable) {
            $value = $value->render();
        }

        if($value instanceof \Generator) {
            $gen = $value;
            $value = null;

            foreach($gen as $part) {
                $value .= aura\html\ElementContent::normalize($part);
            }

            if($value !== null) {
                $value = new aura\html\ElementString($value);
            }
        }

        if(is_numeric($value)) {
            $value = $this->_widget->getContext()->html->number($value);
        } else if(is_bool($value)) {
            $value = $this->_widget->getContext()->html->booleanIcon($value);
        } else if($this->_nullToNa && empty($value) && $value != '0') {
            $value = new aura\html\ElementString('<span class="na">n/a</span>');
        } else if($value instanceof core\time\IDate) {
            $value = $this->_widget->getContext()->html->userDate($value);
        }

        return $value;
    }

    public function skipRow() {
        $this->_skipRow = true;
        return $this;
    }

    public function skipCells(int $count=1) {
        $this->_skipCells = $count;
        return $this;
    }

    public function shouldSkipRow(): bool {
        return $this->_skipRow;
    }

    public function shouldSkipCells(): bool {
        return $this->_skipCells;
    }


// Store
    public function setStore($key, $value) {
        $this->_store[$key] = $value;
        return $this;
    }

    public function hasStore(...$keys): bool {
        foreach($keys as $key) {
            if(isset($this->_store[$key])) {
                return true;
            }
        }

        return false;
    }

    public function getStore($key, $default=null) {
        if(isset($this->_store[$key])) {
            return $this->_store[$key];
        }

        return $default;
    }

    public function removeStore(...$keys) {
        foreach($keys as $key) {
            unset($this->_store[$key]);
        }

        return $this;
    }
}
