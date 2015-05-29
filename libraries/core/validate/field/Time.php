<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;

class Time extends Base implements core\validate\ITimeField {
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        try {
            $value = core\time\TimeOfDay::factory($value);
        } catch(\Exception $e) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'This is not a valid time of day'
            ));
        }

        return $this->_finalize($node, $value);
    }
}
