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

class DateTimePicker extends DatePicker {
    
    const INPUT_TYPE = 'datetime-local';
    
    protected $_outputFormat = 'Y-m-d\TH:i';
    protected $_placeholder = 'yyyy-MM-ddThh:mm';

    protected function _getInputType() {
        if($this->_outputFormat != 'Y-m-d\TH:i') {
            return 'text';
        } else {
            return 'datetime-local';
        }
    }

    protected function _stringToDate($date) {
        return core\time\Date::factory((string)$date, true)->toUtc();
    }
    
    protected function _dateToString(core\time\IDate $date) {
        $date->toUserTimezone();
        return $date->format($this->_outputFormat);
    }
}
