<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class WeekPicker extends DatePicker {
    
    const INPUT_TYPE = 'week';
    const DEFAULT_PLACEHOLDER = 'yyyy-Www';
    
    protected function _dateToString(core\time\IDate $date) {
        $date->toUtc();
        return $date->format('Y-\WW');
    }
}
