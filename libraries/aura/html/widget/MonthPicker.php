<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\core;

class MonthPicker extends DatePicker
{
    public const PRIMARY_TAG = 'input.textbox.picker.month';
    public const INPUT_TYPE = 'month';
    public const DEFAULT_PLACEHOLDER = 'yyyy-MM';

    protected function _dateToString(core\time\IDate $date)
    {
        $date->toUtc();
        return $date->format('Y-m');
    }
}
