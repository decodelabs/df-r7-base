<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Url extends Base implements core\validate\IUrlField {

    protected $_allowInternal;

    public function allowInternal(bool $flag=null) {
        if($flag !== null) {
            $this->_allowInternal = $flag;
            return $this;
        }

        return $this->_allowInternal;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if(!$this->_allowInternal) {
            if(!preg_match('/^[a-zA-Z0-9]+\:/', $value)) {
                $value = 'http://'.$value;
            }

            $value = filter_var($value, FILTER_SANITIZE_URL);

            if(!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->_applyMessage($node, 'invalid', $this->validator->_(
                    'This is not a valid URL'
                ));
            }
        }

        return $this->_finalize($node, $value);
    }
}
