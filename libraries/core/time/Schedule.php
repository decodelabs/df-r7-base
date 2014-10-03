<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;

class Schedule implements ISchedule/*, core\IDumpable*/ {
    
    use core\TStringProvider;

    protected static $_ranges = [
        'minute' => [0, 59],
        'hour' => [0, 23],
        'day' => [1, 31],
        'month' => [1, 12],
        'weekday' => [0, 6]
    ];

    protected static $_options = [
        'month' => ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
        'weekday' => ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']
    ];

    protected static $_formatters = [
        'minute' => 'i',
        'hour' => 'G',
        'day' => 'j',
        'month' => 'n',
        'weekday' => 'w'
    ];

    protected $_minute;
    protected $_minuteString = '*';
    protected $_hour;
    protected $_hourString = '*';
    protected $_day;
    protected $_dayString = '*';
    protected $_month;
    protected $_monthString = '*';
    protected $_weekday;
    protected $_weekdayString = '*';

    public static function factory($schedule) {
        if($schedule instanceof ISchedule) {
            return $schedule;
        }

        $keys = ['minute', 'hour', 'day', 'month', 'weekday'];
        $args = array_fill_keys($keys, null);

        if(func_num_args() > 1) {
            $schedule = func_get_args();
        }

        if(is_string($schedule)) {
            $schedule = explode(' ', $schedule);
        }

        if(!is_array($schedule)) {
            throw new InvalidArgumentException(
                'Invalid schedule'
            );
        }

        foreach($schedule as $i => $arg) {
            if(is_int($i) && ($i > 4 || $i < 0)) {
                continue;
            }

            if(is_string($i) && in_array($i, $keys)) {
                $key = $i;
            } else if(!is_numeric($i)) {
                continue;
            } else {
                $key = $keys[$i];
            }

            $args[$key] = $arg;
        }

        return new self($args);
    }

    protected function __construct(array $schedule) {
        foreach($schedule as $key => $value) {
            $this->_set($key, $value);
        }
    }


    public function setMinute($minute) {
        $this->_set('minute', $minute);
        return $this;
    }

    public function getMinute() {
        return $this->_minuteString;
    }

    public function setHour($hour) {
        $this->_set('hour', $hour);
        return $this;
    }

    public function getHour() {
        return $this->_hourString;
    }

    public function setDay($day) {
        $this->_set('day', $day);
        return $this;
    }

    public function getDay() {
        return $this->_dayString;
    }

    public function setMonth($month) {
        $this->_set('month', $month);
        return $this;
    }

    public function getMonth() {
        return $this->_monthString;
    }

    public function setWeekday($weekday) {
        $this->_set('weekday', $weekday);
        return $this;
    }

    public function getWeekday() {
        return $this->_weekdayString;
    }

    protected function _set($key, $value) {
        $min = self::$_ranges[$key][0];
        $max = self::$_ranges[$key][1];
        $options = isset(self::$_options[$key]) ?
            array_flip(self::$_options[$key]) : null;

        if(is_array($value)) {
            $value = array_values($value);
            $string = null;            
        } else if(is_scalar($value)) {
            $string = (string)$value;

            $value = $this->_expandString(
                $string, $min, $max, $options
            );            
        } else {
            throw new InvalidArgumentException(
                'Invalid schedule value'
            );
        }

        if($value === null) {
            $string = '*';
        } else {
            $value = array_map(function($value) use($min, $max) {
                $diff = ($max - $min) + 1;

                while($value < $min) {
                    $value += $diff;
                }

                while($value > $max) {
                    $value -= $diff;
                }

                return $value;
            }, $value);

            $value = array_unique($value);
            sort($value);
        }

        $this->{'_'.$key} = $value;
        $this->{'_'.$key.'String'} = $string;
    }

    protected function _expandString($string, $min, $max, array $options=null) {
        $value = [];
        
        foreach(explode(',', $string) as $part) {
            if($part == '*') {
                return null;
            }

            if(isset($options[$part])) {
                $part = $options[$part];
            }

            if(is_numeric($part)) {
                $value[] = $part;
                continue;
            }

            $parts = explode('/', $part);
            $range = array_shift($parts);

            if($range == '*') {
                $range = [$min, $max];
            } else {
                $range = explode('-', $range, 2);
            }

            $divisor = array_shift($parts);

            if(empty($divisor)) {
                $divisor = null;
            } else {
                $divisor = (int)$divisor;
            }

            if(count($range) == 2) {
                $range = range(array_shift($range), array_shift($range));
            } else {
                $range = [(int)array_shift($range)];
            }

            if($divisor) {
                foreach($range as $rangeVal) {
                    if($rangeVal % $divisor == 0) {
                        $value[] = $rangeVal;
                    }
                }
            } else {
                $value = array_merge($value, $range);
            }
        }

        return $value;
    }



    public function getLast($time=null, $yearThreshold=2) {
        if($time === null) {
            $time = 'now';
        }

        $time = clone core\time\Date::factory($time);
        $reset = false;
        $currentYear = (int)$time->format('Y');

        if($yearThreshold < 0) {
            $yearThreshold = 0;
        } else if($yearThreshold > $currentYear) {
            $yearThreshold = $currentYear;
        }

        while(true) {
            $year = (int)$time->format('Y');

            if($currentYear - $year > $yearThreshold) {
                return null;
            }

            while(!$this->_monthFits($time)) {
                if(!$reset) {
                    $reset = true;
                    $time->modify('23:59:00 last day of -1 month');
                } else {
                    $time->modify('-1 month');
                }
            }

            $month = (int)$time->format('n');

            while(!$this->_dayFits($time)) {
                if(!$reset) {
                    $reset = true;
                    $time->modify('23:59:00');
                } else {
                    $time->modify('-1 day');
                }

                if((int)$time->format('n') != $month) {
                    continue 2;
                }
            }

            $hour = (int)$time->format('G');

            if(!empty($this->_hour)) {
                while(!in_array($hour, $this->_hour)) {
                    $hour--;

                    if($hour < 0) {
                        $time->modify('23:59:00 -1 day');
                        continue 2;
                    }
                }
            }

            $minute = (int)$time->format('i');

            if(!empty($this->_minute)) {
                while(!in_array($minute, $this->_minute)) {
                    $minute--;

                    if($minute < 0) {
                        $time->modify(($time->format('H')-1).':59:00');
                        continue 2;
                    }
                }
            }

            $time->modify($hour.':'.$minute.':00');

            return $time;
        }
    }

    public function getNext($time=null, $yearThreshold=2) {
        if($time === null) {
            $time = 'now';
        }

        $time = clone core\time\Date::factory($time);
        $reset = false;
        $currentYear = (int)$time->format('Y');

        if($yearThreshold < 0) {
            $yearThreshold = 0;
        } else if($yearThreshold > $currentYear) {
            $yearThreshold = $currentYear;
        }

        while(true) {
            $year = (int)$time->format('Y');

            if($year - $currentYear > $yearThreshold) {
                return null;
            }

            while(!$this->_monthFits($time)) {
                if(!$reset) {
                    $reset = true;
                    $time->modify('midnight first day of +1 month');
                } else {
                    $time->modify('+1 month');
                }
            }

            $month = (int)$time->format('n');

            while(!$this->_dayFits($time)) {
                if(!$reset) {
                    $reset = true;
                    $time->modify('midnight +1 day');
                } else {
                    $time->modify('+1 day');
                }

                if((int)$time->format('n') != $month) {
                    continue 2;
                }
            }

            $hour = (int)$time->format('G');

            if(!empty($this->_hour)) {
                while(!in_array($hour, $this->_hour)) {
                    $hour++;

                    if($hour > 23) {
                        $time->modify('midnight +1 day');
                        continue 2;
                    }
                }
            }

            $minute = (int)$time->format('i');

            if(!empty($this->_minute)) {
                while(!in_array($minute, $this->_minute)) {
                    $minute++;

                    if($minute > 59) {
                        $time->modify(($time->format('H')+1).':00:00');
                        continue 2;
                    }
                }
            }

            $time->modify($hour.':'.$minute.':59');

            return $time;
        }
    }

    protected function _monthFits(IDate $time) {
        return empty($this->_month)
            || in_array((int)$time->format('n'), $this->_month);
    }

    protected function _dayFits(IDate $time) {
        return (empty($this->_day) || in_array((int)$time->format('j'), $this->_day))
            && (empty($this->_weekday) || in_array((int)$time->format('w'), $this->_weekday));
    }    


    public function toString() {
        return $this->_minuteString.' '.$this->_hourString.' '.$this->_dayString.' '.$this->_monthString.' '.$this->_weekdayString;
    }


// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}