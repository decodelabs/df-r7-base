<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class UrlTextbox extends Textbox {

    const INPUT_TYPE = 'url';

    protected $_allowInternal = false;

    public function allowInternal($flag=null) {
        if($flag !== null) {
            $this->_allowInternal = (bool)$flag;
            return $this;
        }

        return $this->_allowInternal;
    }

    protected function _getInputType() {
        if($this->_allowInternal) {
            return 'text';
        }

        return parent::_getInputType();
    }
}
