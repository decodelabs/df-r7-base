<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\core;

class TimePicker extends DatePicker
{
    public const PRIMARY_TAG = 'input.textbox.picker.time';
    public const INPUT_TYPE = 'time';

    protected $_outputFormat = 'h:i';
    protected $_placeholder = 'hh:mm';

    protected function _getInputType()
    {
        if ($this->_outputFormat != 'hh:mm') {
            return 'text';
        } else {
            return 'time';
        }
    }

    protected function _normalizeDateString($date)
    {
        if ($date instanceof core\time\IDate) {
            return $this->_dateToString($date);
        }

        if (!$date instanceof core\time\ITimeOfDay) {
            $date = core\time\TimeOfDay::factory($date);
        }

        return sprintf('%02d:%02d', $date->getHours(), $date->getMinutes());
    }

    protected function _dateToString(core\time\IDate $date)
    {
        return $date->format($this->_outputFormat);
    }
}
