<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Email extends Base implements core\validate\IEmailField {

    use core\validate\TStorageAwareField;
    use core\validate\TRecordManipulatorField;
    use opal\query\TFilterConsumer;
    use core\validate\TUniqueCheckerField;


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        $value = strtolower($value);
        $value = str_replace([' at ', ' dot '], ['@', '.'], $value);
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid email address'
            ));
        }

        $this->_validateUnique($value);


        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
