<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;
use df\flex;

class Slug extends Base implements core\validate\ISlugField {

    use core\validate\TStorageAwareField;
    use core\validate\TRecordManipulatorField;
    use opal\query\TFilterConsumer;
    use core\validate\TUniqueCheckerField;
    use core\validate\TMinLengthField;
    use core\validate\TMaxLengthField;

    protected $_allowPathFormat = false;
    protected $_allowAreaMarker = false;
    protected $_allowRoot = false;
    protected $_defaultValueField = null;
    protected $_defaultValueSanitizer = null;
    protected $_generateIfEmpty = false;
    protected $_renameOnConflict = true;

    public function allowPathFormat(bool $flag=null) {
        if($flag !== null) {
            $this->_allowPathFormat = $flag;
            return $this;
        }

        return $this->_allowPathFormat;
    }

    public function allowAreaMarker(bool $flag=null) {
        if($flag !== null) {
            $this->_allowAreaMarker = $flag;
            return $this;
        }

        return $this->_allowAreaMarker;
    }

    public function allowRoot(bool $flag=null) {
        if($flag !== null) {
            $this->_allowRoot = $flag;
            return $this;
        }

        return $this->_allowRoot;
    }

    public function setDefaultValueField($field, $sanitizer=false) {
        $this->_defaultValueField = $field;

        if($sanitizer !== null) {
            if($sanitizer !== false) {
                $sanitizer = core\lang\Callback::factory($sanitizer);
            }

            $this->_defaultValueSanitizer = $sanitizer;
        }

        return $this;
    }

    public function getDefaultValueField() {
        return $this->_defaultValueField;
    }

    public function shouldGenerateIfEmpty(bool $flag=null) {
        if($flag !== null) {
            $this->_generateIfEmpty = $flag;
            return $this;
        }

        return $this->_generateIfEmpty;
    }

    public function shouldRenameOnConflict(bool $flag=null) {
        if($flag !== null) {
            $this->_renameOnConflict = $flag;
            return $this;
        }

        return $this->_renameOnConflict;
    }



    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value, false);
        $value = $this->_sanitizeSlugValue($value);
        $value = $this->_sanitizeValue($value, true);

        if(false !== strpos($value, '/') && !$this->_allowPathFormat) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'Path type slugs are not allowed here'
            ));
        }

        if($this->_allowPathFormat && substr($value, -1) == '/' && strlen($value) > 1) {
            $this->_applyMessage($node, 'required', $this->validator->_(
                'You must enter a full path slug'
            ));

            return null;
        }

        if($value == '/' && !$this->_allowRoot) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'Root slug is not allowed here'
            ));
        }


        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        $this->_validateMinLength($node, $value, $length);
        $this->_validateMaxLength($node, $value, $length);

        $this->_validateUnique($node, $value, $this->_renameOnConflict);
        return $this->_finalize($node, $value);
    }

    protected function _sanitizeSlugValue($value) {
        $value = trim($value);

        if(empty($value) && $this->_defaultValueField) {
            $data = $this->validator->getCurrentData();

            if($data->has($this->_defaultValueField)) {
                $value = trim($data[$this->_defaultValueField]);

                if($this->_defaultValueSanitizer) {
                    $value = $this->_defaultValueSanitizer->invoke($value, $this);
                }
            }
        }

        if(empty($value) && $this->_generateIfEmpty) {
            $value = flex\Generator::random();
        }

        if($this->_allowPathFormat) {
            $value = flex\Text::formatPathSlug($value, $this->_allowAreaMarker ? '~' : null);
        } else {
            $value = flex\Text::formatSlug($value);
        }

        return $value;
    }
}