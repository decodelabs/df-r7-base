<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class DateTimePicker extends DatePicker {
    
    const INPUT_TYPE = 'datetime';
    const DEFAULT_PLACEHOLDER = 'yyyy-MM-ddThh:mmZ (UTC)';
    
    protected function _dateToString(core\time\IDate $date) {
        $date->toUtc();
        return $date->format('Y-m-d\TH:i\Z');
    }
}
