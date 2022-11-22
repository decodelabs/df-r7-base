<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\Dictum;
use df\core;

use df\opal;

class Text extends Base implements core\validate\ITextField
{
    use core\validate\TStorageAwareField;
    use core\validate\TRecordManipulatorField;
    use opal\query\TFilterConsumer;
    use core\validate\TUniqueCheckerField;
    use core\validate\TMinLengthField;
    use core\validate\TMaxLengthField;

    protected $_pattern = null;
    protected $_minWords = null;
    protected $_maxWords = null;
    protected $_shouldTrim = true;
    protected $_accept4Byte = true;


    // Pattern
    public function setPattern($pattern)
    {
        if (empty($pattern)) {
            $pattern = null;
        }

        $this->_pattern = $pattern;
        return $this;
    }

    public function getPattern()
    {
        return $this->_pattern;
    }


    // Word length
    public function setMinWords($length)
    {
        if ($length !== null) {
            $length = (int)$length;

            if (empty($length)) {
                $length = 0;
            }

            if ($length < 0) {
                $length = 0;
            }
        }

        $this->_minWords = $length;
        return $this;
    }

    public function getMinWords()
    {
        return $this->_minWords;
    }

    public function setMaxWords($length)
    {
        if ($length !== null) {
            $length = (int)$length;

            if (empty($length)) {
                $length = 0;
            }

            if ($length < 0) {
                $length = 0;
            }
        }

        $this->_maxWords = $length;
        return $this;
    }

    public function getMaxWords()
    {
        return $this->_maxWords;
    }


    // Trim
    public function shouldTrim(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldTrim = $flag;
            return $this;
        }

        return $this->_shouldTrim;
    }


    // 4 Byte
    public function canAccept4Byte(bool $flag = null)
    {
        if ($flag != null) {
            $this->_accept4Byte = $flag;
            return $this;
        }

        return $this->_accept4Byte;
    }


    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->data->getValue();

        if ($this->_shouldTrim) {
            $value = trim($value);
        }

        $value = $this->_sanitizeValue($value);


        if (!$length = $this->_checkRequired($value)) {
            return null;
        }



        // Validate
        if (
            !$this->_accept4Byte &&
            preg_match('%(?:
                \xF0[\x90-\xBF][\x80-\xBF]{2} |     # planes 1-3
                [\xF1-\xF3][\x80-\xBF]{3} |         # planes 4-15
                \xF4[\x80-\x8F][\x80-\xBF]{2}       # plane 16
            )%xs', $value)
        ) {
            $this->addError('4byte', $this->validator->_('Sorry, we can\'t currently accept emojis'));
            return;
        }


        $this->_validateMinLength($value, $length);
        $this->_validateMaxLength($value, $length);

        if ($this->_minWords !== null || $this->_maxWords !== null) {
            $wordCount = Dictum::countWords($value);

            if ($this->_minWords !== null && $wordCount < $this->_minWords) {
                $this->addError('minWords', $this->validator->_(
                    [
                        'n = 1' => 'This field must contain at least %min% word',
                        '*' => 'This field must contain at least %min% words'
                    ],
                    ['%min%' => $this->_minWords],
                    $this->_minWords
                ));
            }

            if ($this->_maxWords !== null && $wordCount > $this->_maxWords) {
                $this->addError('maxWords', $this->validator->_(
                    [
                        'n = 1' => 'This field must not be more than %max% word',
                        '*' => 'This field must not be more than %max% words'
                    ],
                    ['%max%' => $this->_maxWords],
                    $this->_maxWords
                ));
            }
        }

        if ($this->_pattern !== null && !filter_var(
            $value,
            FILTER_VALIDATE_REGEXP,
            ['options' => ['regexp' => $this->_pattern]]
        )) {
            $this->addError('pattern', $this->validator->_('The value entered is invalid'));
        }

        $this->_validateUnique($value);


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
