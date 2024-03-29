<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

class UrlTextbox extends Textbox
{
    public const PRIMARY_TAG = 'input.textbox.url';
    public const INPUT_TYPE = 'url';

    protected $_allowInternal = false;

    public function allowInternal(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowInternal = $flag;
            return $this;
        }

        return $this->_allowInternal;
    }

    protected function _getInputType()
    {
        if ($this->_allowInternal) {
            return 'text';
        }

        return parent::_getInputType();
    }
}
