<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;
use df\user;

class Date implements IDate, core\IDumpable {
    
    const SHORT = \IntlDateFormatter::SHORT;
    const MEDIUM = \IntlDateFormatter::MEDIUM;
    const LONG = \IntlDateFormatter::LONG;
    const FULL = \IntlDateFormatter::FULL;

    const ATOM = \DateTime::ATOM;
    const COOKIE = \DateTime::COOKIE;
    const ISO8601 = \DateTime::ISO8601;
    const RFC822 = \DateTime::RFC822;
    const RFC850 = \DateTime::RFC850;
    const RFC1036 = \DateTime::RFC1036;
    const RFC1123 = \DateTime::RFC1123;
    const RFC2822 = \DateTime::RFC2822;
    const RFC3339 = \DateTime::RFC3339;
    const RSS = \DateTime::RSS;
    const W3C = \DateTime::W3C;
    const DB = 'Y-m-d H:i:s';
    const DBDATE = 'Y-m-d';
    
    protected $_date;
    
    public static function fromCompressedString($string, $timezone=true) {
        if($string instanceof IDate) {
            return $string;
        }

        $date = substr($string, 0, 8);
        $date = substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);

        if(strlen($string) == 14) {
            $time = substr($string, 8);
            $time = substr($time, 0, 2).':'.substr($time, 2, 2).':'.substr($time, 4, 2);
            $date .= ' '.$time;
        }

        return new self($date, $timezone);
    }

    public static function fromLocaleString($string, $timezone=true, $size=self::SHORT, $locale=null) {
        if($string instanceof IDate) {
            return $string;
        }
        
        $timezone = self::_normalizeTimezone($timezone);
        $locale = (string)core\i18n\Locale::factory($locale);
        
        $formatter = new \IntlDateFormatter(
            $locale,
            $size,
            $size,
            $timezone->getName()
        );
        
        return new self($formatter->parse($string), $timezone);
    }

    public static function fromFormatString($date, $format, $timezone=true, $locale=null) {
        if($date instanceof IDate) {
            return $date;
        }

        $timezone = self::_normalizeTimezone($timezone);
        $locale = (string)core\i18n\Locale::factory($locale);

        $date = \DateTime::createFromFormat($format, $date, $timezone);
        return new self($date);
    }
    
    public static function factory($date, $timezone=null) {
        if($date instanceof IDuration) {
            $date = '+'.$date->getSeconds().' seconds';
        }

        if($date instanceof IDate) {
            return $date;
        }
        
        return new self($date, $timezone);
    }
    
    private static function _normalizeTimezone($timezone) {
        if($timezone === false) {
            $timezone = null;
        } else if($timezone === true) {
            $userManager = user\Manager::getInstance();
            $timezone = $userManager->getClient()->getTimezone(); 
        }
        
        if($timezone === null) {
            $timezone = 'UTC';
        }
        
        if(!$timezone instanceof \DateTimeZone) {
            try {
                $timezone = new \DateTimeZone((string)$timezone);
            } catch(\Exception $e) {
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        
        return $timezone;
    }
    
    public function __construct($date=null, $timezone=null) {
        if($date instanceof self) {
            $this->_date = clone $date->_date;
            return;
        } else if($date instanceof \DateTime) {
            $this->_date = $date;
            return;
        }
        
        $timestamp = null;
        
        if(is_numeric($date)) {
            $timestamp = $date;
            $date = 'now';
        }
        
        $timezone = self::_normalizeTimezone($timezone);
        
        if($date === null) {
            $date = 'now';
        }
        
        try {
            $this->_date = new \DateTime($date, $timezone);
        } catch(\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        
        if($timestamp !== null) {
            $this->_date->setTimestamp($timestamp);
        }
    }
    
    
// Serialize
    public function serialize() {
        return $this->__toString();
    }
    
    public function unserialize($string) {
        $this->_date = new \DateTime($string);
        return $this;
    }
    
    
// Duplicate
    public function __clone() {
         $this->_date = clone $this->_date;
         return $this;
    }
    
    
// Time zone
    public function toUserTimezone() {
        try {
            $client = $userManager = user\Manager::getInstance()->getClient();
            $timezone = new \DateTimeZone($client->getTimezone());
            $this->_date->setTimezone($timezone);    
        } catch(\Exception $e) {}
        
        return $this;
    }
    
    public function toUtc() {
        $this->_date->setTimezone(new \DateTimeZone('UTC'));
        return $this;
    }
    
    public function toTimezone($timezone) {
        if(!$timezone instanceof \DateTimeZone) {
            try {
                $timezone = new \DateTimeZone((string)$timezone);
            } catch(\Exception $e) {
                throw new InvalidArgumentException($e->getMessage());
            }
        }
        
        $this->_date->setTimezone($timezone);
        
        return $this;
    }
    
    public function getTimezone() {
        return $this->_date->getTimezone()->getName();
    }

    public function getTimezoneAbbreviation() {
        return $this->_date->format('T');
    }
    
    
// Formatting
    public function toString() {
        return $this->format('c');
    }

    public function __toString() {
        try {
            return $this->format('c');
        } catch(\Exception $e) {
            return '0000-00-00';
        }
    }
    
    public function toTimestamp() {
        return $this->_date->getTimestamp();
    }
    
    public function userLocaleFormat($size=self::LONG) {
        $tz = $this->_date->getTimezone();
        $this->toUserTimezone();
        
        $output = $this->localeFormat($size);
        $this->toTimezone($tz);
        
        return $output;
    }
    
    public function localeFormat($size=self::LONG, $locale=null) {
        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);
        
        $output = \IntlDateFormatter::create($locale, $size, $size, $this->getTimezone());

        if($output) {
            return $output->format($this->toTimestamp());
        } else {
            return $this->format('Y-m-d H:i:s');
        }
    }
    
    public function userLocaleDateFormat($size='long') {
        $tz = $this->_date->getTimezone();
        $this->toUserTimezone();
        
        $output = $this->localeDateFormat($size);
        $this->toTimezone($tz);
        
        return $output;
    }
    
    public function localeDateFormat($size='long', $locale=true) {
        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);
        
        return \IntlDateFormatter::create($locale, $size, \IntlDateFormatter::NONE, $this->getTimezone())
            ->format($this->toTimestamp());
    }
    
    public function userLocaleTimeFormat($size='long') {
        $tz = $this->_date->getTimezone();
        $this->toUserTimezone();
        
        $output = $this->localeTimeFormat($size);
        $this->toTimezone($tz);
        
        return $output;
    }
    
    public function localeTimeFormat($size='long', $locale=true) {
        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);
        
        return \IntlDateFormatter::create($locale, \IntlDateFormatter::NONE, $size, $this->getTimezone())
            ->format($this->toTimestamp());
    }
    
    protected function _normalizeFormatterSize($size) {
        if(is_string($size)) {
            $size = strtolower($size);

            switch($size) {
                case 'full':
                    return self::FULL;
                
                case 'long':
                    return self::LONG;
                    
                case 'medium':
                    return self::MEDIUM;
                    
                case 'short':
                    return self::SHORT;
            }
        }
        
        switch($size) {
            case self::FULL:
            case self::LONG:
            case self::MEDIUM:
                return $size;
                
            case self::SHORT:
            default:
                return self::SHORT;
        }
    }
    
    public function userFormat($format='Y-m-d H:i:s T') {
        $tz = $this->getTimezone();
        $this->toUserTimezone();
        
        $output = $this->format($format);
        $this->toTimezone($tz);
        
        return $output;    
    }
    
    public function format($format='Y-m-d H:i:s T') {
        return $this->_date->format($format);
    }
    
    
// Comparison
    public function eq($date) {
        if($date === null) {
            return false;
        }
        
        return $this->toTimestamp() == self::factory($date)->toTimestamp();
    }
    
    public function gt($date) {
        if($date === null) {
            return true;
        }
        
        return $this->toTimestamp() > self::factory($date)->toTimestamp();
    }
    
    public function gte($date) {
        if($date === null) {
            return true;
        }
        
        return $this->toTimestamp() >= self::factory($date)->toTimestamp();
    }
    
    public function lt($date) {
        if($date === null) {
            return false;
        }
        
        return $this->toTimestamp() < self::factory($date)->toTimestamp();
    }
    
    public function lte($date) {
        if($date === null) {
            return false;
        }
        
        return $this->toTimestamp() <= self::factory($date)->toTimestamp();
    }

    public function isPast() {
        return $this->lt('now');
    }

    public function isNearPast($hours=null) {
        if(empty($hours)) {
            $hours = 24;
        }

        return $this->lt('now') && $this->gte('-'.(int)$hours.' hours');
    }

    public function isFuture() {
        return $this->gt('now');
    }

    public function isNearFuture($hours=null) {
        if(empty($hours)) {
            $hours = 24;
        }

        return $this->gt('now') && $this->lte('+'.(int)$hours.' hours');
    }
    
    
// Modification
    public function modify($string) {
        $this->_date->modify($string);
        return $this;
    }

    public function modifyNew($string) {
        $output = clone $this;
        $output->_date->modify($string);
        return $output;
    }
    
    public function add($duration) {
        $seconds = Duration::factory($duration)->getSeconds();
        
        if($seconds < 0) {
            $string = $seconds.' seconds';
        } else {
            $string = '+'.$seconds.' seconds';
        }
        
        $this->_date->modify($string);
        return $this;
    }

    public function addNew($duration) {
        $output = clone $this;
        return $output->add($duration);
    }
    
    public function subtract($duration) {
        $seconds = round(Duration::factory($duration)->getSeconds());
        
        if($seconds < 0) {
            $string = '+'.($seconds * -1).' seconds';
        } else {
            $string = '-'.$seconds.' seconds';
        }
        
        $this->_date->modify($string);
        return $this;
    }

    public function subtractNew($duration) {
        $output = clone $this;
        return $output->subtract($duration);
    }
    
    
// Duration
    public function timeSince() {
        return new Duration(time() - $this->toTimestamp(), clone $this);
    }
    
    public function timeUntil() {
        return new Duration($this->toTimestamp() - time(), clone $this);
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->format('Y-m-d H:i:s T');
    }
    
    
    
    
    
// Utils
    private static $_months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    private static $_years = [
        1970 => 0, 1960 => -315619200, 1950 => -631152000, 1940 => -946771200, 1930 => -1262304000,
        1920 => -1577923200, 1910 => -1893456000, 1900 => -2208988800, 1890 => -2524521600,
        1880 => -2840140800, 1870 => -3155673600, 1860 => -3471292800, 1850 => -3786825600,
        1840 => -4102444800, 1830 => -4417977600, 1820 => -4733596800, 1810 => -5049129600,
        1800 => -5364662400, 1790 => -5680195200, 1780 => -5995814400, 1770 => -6311347200,
        1760 => -6626966400, 1750 => -6942499200, 1740 => -7258118400, 1730 => -7573651200,
        1720 => -7889270400, 1710 => -8204803200, 1700 => -8520336000, 1690 => -8835868800,
        1680 => -9151488000, 1670 => -9467020800, 1660 => -9782640000, 1650 => -10098172800,
        1640 => -10413792000,1630 => -10729324800,1620 => -11044944000,1610 => -11360476800,
        1600 => -11676096000
    ];

    const YEAR = 31536000;
    const DAY = 86400;
    const HOUR = 3600;
    const MIN = 60;

    public static function monthDays($month, $leapYear=false) {
        $month--;
        $output = self::$_months[$month];
        
        if($month == 1 && $leapYear) {
            $output++;
        }
        
        return $output;
    }

    public static function isLeapYear($year) {
        $year = self::correctYearDigit($year);
        
        if($year % 4 != 0) {
            return false;
        }
        
        if($year % 400 == 0) {
            return true;
        } else if(($year > 1582) && ($year % 100 == 0)) {
            return false;
        }
        
        return true;
    }

    public static function correctYearDigit($year) {
        if($year < 100) {
            $yr = (int)date("Y");
            $century = (int)($yr / 100);

            if($yr % 100 > 50) {
                $c1 = $century + 1;
                $c0 = $century;
            } else {
                $c1 = $century;
                $c0 = $century - 1;
            }
            
            $c1 *= 100;
            
            if(($year + $c1) < $yr + 30) {
                $year = $year + $c1;
            } else {
                $year = $year + $c0 * 100;
            }
        }
        
        return $year;
    }

    public static function dayOfWeek($year, $month, $day) {
        if($year > 1901 && $year < 2038) {
            return (int)date('w', mktime(0, 0, 0, $month, $day, $year));
        }
        
        $correction = 0;
        
        if(($year < 1582) || (($year == 1582) && (($month < 10) || (($month == 10) && ($day < 15))))) {
            $correction = 3;
        }

        if($month > 2) {
            $month -= 2;
        } else {
            $month += 10;
            $year--;
        }

        $day  = floor((13 * $month - 1) / 5) + $day + ($year % 100) + floor(($year % 100) / 4);
        $day += floor(($year / 100) / 4) - 2 * floor($year / 100) + 77 + $correction;

        return (int)($day - 7 * floor($day / 7));
    }

    public static function weekNumber($year, $month, $day) {
        if($year > 1901 && $year < 2038) {
            return (int)date('W', mktime(0, 0, 0, $month, $day, $year));
        }

        $dayofweek = self::dayOfWeek($year, $month, $day);
        $firstday  = $day1 = self::dayOfWeek($year, 1, 1);
        $testDay = self::dayOfWeek($year + 1, 1, 1);
        
        if(($month == 1) && (($firstday < 1) || ($firstday > 4)) && ($day < 4)) {
            $firstday  = self::dayOfWeek($year - 1, 1, 1);
            $month     = 12;
            $day       = 31;
        } else if(($month == 12) && (($testDay < 5) && ($testDay > 0))) {
            return 1;
        }
        
        return intval((($day1 < 5) && ($day1 > 0)) + 4 * ($month - 1) + (2 * ($month - 1) + ($day - 1) + $firstday - $dayofweek + 6) * 36 / 256);
    }
}