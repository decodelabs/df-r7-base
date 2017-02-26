<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\neon;

class Color extends Base implements core\validate\IColorField {

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        try {
            $value = neon\Color::factory($value);
        } catch(\Throwable $e) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'Please enter a valid color'
            ));

            return null;
        }

        $value = $this->_applyCustomValidator($node, $value);

        if($this->_shouldSanitize) {
            $node->setValue((string)$value);
        }

        return $value;
    }
}