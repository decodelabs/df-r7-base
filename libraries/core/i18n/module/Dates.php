<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n\module;

use df\core;

class Dates extends Base
{
    public function getCalendarList()
    {
        $this->_loadData();

        $output = $this->_data;
        unset($output['@default']);

        return array_keys($output);
    }

    public function getDefaultCalendar()
    {
        $this->_loadData();

        if (isset($this->_data['@default'])
        && isset($this->_data[$this->_data['@default']])) {
            return $this->_data['@default'];
        }

        return 'gregorian';
    }

    public function getDayName($day = null, $calendar = null)
    {
        $this->_loadData();

        $list = $this->getDayList($calendar);

        if ($day === null) {
            $day = core\time\Date::factory('now')->format('w');
        }

        switch (strtolower($day)) {
            case 0:
            case 7:
            case 'sun':
            case 'sunday':
                return $list['sun'];

            case 1:
            case 'mon':
            case 'monday':
                return $list['mon'];

            case 2:
            case 'tue':
            case 'tuesday':
                return $list['tue'];

            case 3:
            case 'wed':
            case 'wednesday':
                return $list['wed'];

            case 4:
            case 'thu':
            case 'thursday':
                return $list['thur'];

            case 5:
            case 'fri':
            case 'friday':
                return $list['fri'];

            case 6:
            case 'sat':
            case 'saturday':
                return $list['sat'];
        }
    }

    public function getDayList($calendar = null)
    {
        $this->_loadData();

        $calendar = strtolower((string)$calendar);

        if (!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }

        $c = $this->_data[$calendar];

        if (is_string($c['days'])) {
            return $this->getDayList($c['days']);
        }

        return $c['days']['full'];
    }

    public function getAbbreviatedDayList($calendar = null)
    {
        $this->_loadData();

        $calendar = strtolower((string)$calendar);

        if (!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }

        $c = $this->_data[$calendar];

        if (is_string($c['days'])) {
            return $this->getDayList($c['days']);
        }

        if (is_string($c['days']['abbreviated'])) {
            return $this->getDayList($calendar);
        }

        return $c['days']['abbreviated'];
    }

    public function getMonthName($month = null, $calendar = null)
    {
        $this->_loadData();

        $list = $this->getMonthList($calendar);

        if ($month === null) {
            $month = core\time\Date::factory('now')->format('n');
        }

        switch (strtolower($month)) {
            case 1:
            case 'jan':
            case 'january':
                return $list[1];

            case 2:
            case 'feb':
            case 'february':
                return $list[2];

            case 3:
            case 'mar':
            case 'march':
                return $list[3];

            case 4:
            case 'apr':
            case 'april':
                return $list[4];

            case 5:
            case 'may':
                return $list[5];

            case 6:
            case 'jun':
            case 'june':
                return $list[6];

            case 7:
            case 'jul':
            case 'july':
                return $list[7];

            case 8:
            case 'aug':
            case 'august':
                return $list[8];

            case 9:
            case 'sep':
            case 'september':
                return $list[9];

            case 10:
            case 'oct':
            case 'october':
                return $list[10];

            case 11:
            case 'nov':
            case 'november':
                return $list[11];

            case 12:
            case 'dec':
            case 'december':
                return $list[12];
        }
    }

    public function getMonthList($calendar = null)
    {
        $this->_loadData();

        $calendar = strtolower((string)$calendar);

        if (!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }

        $c = $this->_data[$calendar];

        if (is_string($c['months'])) {
            return $this->getMonthList($c['months']);
        }

        return $c['months']['full'];
    }

    public function getAbbreviatedMonthList($calendar = null)
    {
        $this->_loadData();

        $calendar = strtolower((string)$calendar);

        if (!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }

        $c = $this->_data[$calendar];

        if (is_string($c['months'])) {
            return $this->getAbbreviatedMonthList($c['months']);
        }

        if (is_string($c['months']['abbreviated'])) {
            return $this->getMonthList($calendar);
        }

        return $c['months']['abbreviated'];
    }
}
