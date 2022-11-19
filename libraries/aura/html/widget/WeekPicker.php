<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\core;

class WeekPicker extends DatePicker
{
    public const PRIMARY_TAG = 'input.textbox.picker.week';
    public const INPUT_TYPE = 'week';
    public const DEFAULT_PLACEHOLDER = 'yyyy-Www';

    protected function _dateToString(core\time\IDate $date)
    {
        $date->toUtc();
        return $date->format('Y-\WW');
    }
}
