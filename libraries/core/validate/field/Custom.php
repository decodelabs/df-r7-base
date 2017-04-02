<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Custom extends Base implements core\validate\ICustomField {

    protected $_validator;

    public function setValidator(callable $validator) {
        $this->_validator = core\lang\Callback::factory($validator);
        return $this;
    }

    public function getValidator() {
        return $this->_validator;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        $this->_validator->invoke($node, $value);
        return $this->_finalize($node, $value);
    }
}
