<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\flex;

class Json extends Base implements core\validate\IStructureField
{
    protected $_allowEmpty = false;

    // Options
    public function shouldAllowEmpty(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_allowEmpty = $flag;
            return $this;
        }

        return $this->_allowEmpty;
    }


    // Validate
    public function validate()
    {
        // Sanitize
        $origValue = $value = $this->_sanitizeValue($this->data->getValue());

        if (
            !$value instanceof core\collection\ITree &&
            $value !== null
        ) {
            try {
                $value = flex\Json::stringToTree((string)$value);
            } catch (\Throwable $e) {
                $this->addError('invalid', $this->validator->_(
                    'This does not appear to be valid JSON'
                ));
                return $origValue;
            }
        }

        if ($value) {
            if (
                $value->isEmpty() &&
                !$value->hasValue() &&
                !empty($origValue)
            ) {
                $this->addError('invalid', $this->validator->_(
                    'This does not appear to be valid JSON'
                ));
                return $origValue;
            }

            $stringValue = flex\Json::toString($value);
        } else {
            $stringValue = null;
        }

        if (!$length = $this->_checkRequired($stringValue)) {
            return null;
        }



        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue(flex\Json::toString($value, \JSON_PRETTY_PRINT));

        return $value;
    }
}
