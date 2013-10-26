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
    const DEFAULT_PLACEHOLDER = 'yyyy-MM-ddThh:mm';
    
    protected function _stringToDate($date) {
        return core\time\Date::factory((string)$date, true)->toUtc();
    }
    
    protected function _dateToString(core\time\IDate $date) {
        $date->toUserTimezone();
        return $date->format('Y-m-d\TH:i');
    }
}
