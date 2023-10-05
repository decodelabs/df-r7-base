<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use df\arch;

class Textarea extends Base implements ITextareaWidget
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_PlaceholderProvider;
    use TWidget_TextEntry;

    public const PRIMARY_TAG = 'textarea.textbox.multiline';
    public const ARRAY_INPUT = false;

    protected $_columns;
    protected $_rows;
    protected $_directionInputName;
    protected $_wrap;

    public function __construct(arch\IContext $context, $name, $value = null)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyPlaceholderAttributes($tag);
        $this->_applyTextEntryAttributes($tag);

        if ($this->_columns !== null) {
            $tag->setAttribute('cols', (int)$this->_columns);
        }

        if ($this->_rows !== null) {
            $tag->setAttribute('rows', (int)$this->_rows);
        }

        if ($this->_directionInputName !== null) {
            $tag->setAttribute('dirname', $this->_directionInputName);
        }

        if ($this->_wrap !== null) {
            $tag->setAttribute('wrap', $this->_wrap);
        }

        return $tag->renderWith($this->getValueString());
    }


    // Columns
    public function setColumns($columns)
    {
        $this->_columns = $columns;
        return $this;
    }

    public function getColumns()
    {
        return $this->_columns;
    }


    // Rows
    public function setRows($rows)
    {
        $this->_rows = $rows;
        return $this;
    }

    public function getRows()
    {
        return $this->_rows;
    }


    // Direction
    public function setDirectionInputName($id)
    {
        $this->_directionInputName = $id;
        return $this;
    }

    public function getDirectionInputName()
    {
        return $this->_directionInputName;
    }


    // Wrap
    public function setWrap($wrap)
    {
        if (!$wrap) {
            $this->_wrap = null;
        } else {
            switch ($wrap = strtolower((string)$wrap)) {
                case 'soft':
                case 'hard':
                    $this->_wrap = $wrap;
                    break;

                default:
                    $this->_wrap = null;
                    break;
            }
        }

        return $this;
    }

    public function getWrap()
    {
        return $this->_wrap;
    }
}
