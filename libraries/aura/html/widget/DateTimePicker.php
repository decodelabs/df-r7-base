<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\arch;
use df\aura;
use df\core;

class DateTimePicker extends DatePicker
{
    public const PRIMARY_TAG = 'input.textbox.picker.datetime';
    public const INPUT_TYPE = 'datetime-local';

    protected $_outputFormat = 'Y-m-d\TH:i';
    protected $_placeholder = 'yyyy-MM-ddThh:mm';
    protected $_timezone = null;
    protected $_showTimezone = true;


    public function __construct(arch\IContext $context, $name, $value = null, $timezone = null)
    {
        $this->_timezone = $timezone;
        parent::__construct($context, $name, $value);
    }

    protected function _getInputType()
    {
        if ($this->_outputFormat != 'Y-m-d\TH:i') {
            return 'text';
        } else {
            return 'datetime-local';
        }
    }

    protected function _render()
    {
        $output = parent::_render();

        if ($this->_showTimezone) {
            $date = $this->_stringToDate($this->getValue()->getValue());

            if ($this->_timezone !== null) {
                $date->toTimezone($this->_timezone);
            } else {
                $date->toUserTimezone();
            }

            $output = new aura\html\Element('label.datetime', [$output, ' ',
                new aura\html\Element('abbr.timezone', $date->getTimezoneAbbreviation(), ['title' => $date->getTimezone()])
            ]);
        }

        return $output;
    }

    public function shouldShowTimezone(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_showTimezone = $flag;
            return $this;
        }

        return $this->_showTimezone;
    }

    public function getTimezone()
    {
        return $this->_timezone;
    }

    protected function _stringToDate($date)
    {
        if ($this->_outputFormat != 'Y-m-d\TH:i') {
            $output = core\time\Date::fromFormatString((string)$date, $this->_outputFormat, $this->_timezone ?? true);
        } else {
            $output = core\time\Date::factory((string)$date, $this->_timezone ?? true);
        }

        $output->toUtc();
        return $output;
    }

    protected function _dateToString(core\time\IDate $date)
    {
        if ($this->_timezone !== null) {
            $date->toTimezone($this->_timezone);
        } else {
            $date->toUserTimezone();
        }

        return $date->format($this->_outputFormat);
    }
}
