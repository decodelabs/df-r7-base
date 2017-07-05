<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class Duration extends Textbox {

    const PRIMARY_TAG = 'input.textbox.duration';

    protected $_placeholder = 'eg. 3 days 4 hours, hh:mm:ss or x number of seconds';

    public function setValue($value) {
        $innerValue = $value;

        if($innerValue instanceof core\IValueContainer) {
            $innerValue = $innerValue->getValue();
        }

        if(is_string($innerValue) && !strlen($innerValue)) {
            $innerValue = null;
        }

        if($innerValue !== null) {
            $innerValue = $this->_normalizeDurationString($innerValue);
        }

        if($value instanceof core\IValueContainer) {
            $value->setValue($innerValue);
        } else {
            $value = $innerValue;
        }

        return parent::setValue($value);
    }

    protected function _normalizeDurationString($duration) {
        try {
            $duration = core\time\Duration::factory($duration);
        } catch(\Throwable $e) {
            $duration = null;
        }

        if($duration !== null) {
            $duration = $this->_durationToString($duration);
        }

        return $duration;
    }

    protected function _durationToString(core\time\IDuration $duration) {
        return $duration->getUserString();
    }
}
