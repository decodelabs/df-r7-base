<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use df\core;

class Url extends Base implements core\validate\IUrlField
{
    protected $_allowInternal;


    // Options
    public function allowInternal(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowInternal = $flag;
            return $this;
        }

        return $this->_allowInternal;
    }



// Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        if (!$this->_allowInternal) {
            if (!preg_match('/^[a-zA-Z0-9]+\:/', (string)$value)) {
                $value = 'http://' . $value;
            }

            $value = filter_var($value, FILTER_SANITIZE_URL);

            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->addError('invalid', $this->validator->_(
                    'This is not a valid URL'
                ));
            }
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
