<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\mesh;

class Enum extends Base implements core\validate\IEnumField {

    use core\validate\TOptionProviderField;

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if($this->_type) {
            try {
                $value = $this->_type->factory($value)->getOption();
            } catch(core\lang\EEnum $e) {
                $this->_applyMessage($node, 'invalid', $this->validator->_(
                    'Please select a valid option'
                ));
            }
        } else {
            if(isset($this->_options[$value])) {
                $value = (string)$this->_options[$value];
                $this->_shouldSanitize = false;
            }

            if(!in_array($value, $this->_options)) {
                $this->_applyMessage($node, 'invalid', $this->validator->_(
                    'Please select a valid option'
                ));
            }
        }

        return $this->_finalize($node, $value);
    }
}