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

class MonthPicker extends DatePicker {

    const PRIMARY_TAG = 'input.textbox.picker.month';
    const INPUT_TYPE = 'month';
    const DEFAULT_PLACEHOLDER = 'yyyy-MM';

    protected function _dateToString(core\time\IDate $date) {
        $date->toUtc();
        return $date->format('Y-m');
    }
}
