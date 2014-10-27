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
    
    public $_date;
    
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
    
    public function is($date) {
        if($date === null) {
            return false;
        }

        return $this->format('Y-m-d') == self::factory($date)->format('Y-m-d');
    }

    public function isBetween($start, $end) {
        if($start === null && $end === null) {
            return false;
        }

        if($start !== null && !$this->gte($start)) {
            return false;
        }

        if($end !== null && !$this->lte($end)) {
            return false;
        }

        return true;
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
        return $this->toTimestamp() < time();
    }

    public function isNearPast($hours=null) {
        if(empty($hours)) {
            $hours = 24;
        }

        $ts = $this->toTimestamp();
        $time = time();

        return $ts < $time && $ts > $time - ($hours * 60);
    }

    public function isFuture() {
        return $this->toTimestamp() > time();
    }

    public function isNearFuture($hours=null) {
        if(empty($hours)) {
            $hours = 24;
        }

        $ts = $this->toTimestamp();
        $time = time();

        return $ts > $time && $ts < $time + ($hours * 60);
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
    
    public function add($interval) {
        $interval = $this->_normalizeInterval($interval);
        $this->_date->add($interval);
        return $this;
    }

    public function addNew($interval) {
        $output = clone $this;
        return $output->add($interval);
    }
    
    public function subtract($interval) {
        $interval = $this->_normalizeInterval($interval);
        $this->_date->sub($interval);
        return $this;
    }

    public function subtractNew($interval) {
        $output = clone $this;
        return $output->subtract($interval);
    }

    protected function _normalizeInterval($interval) {
        $seconds = null;

        if($interval instanceof IDuration) {
            $seconds = $interval->getSeconds();
        } else if(is_numeric($interval)) {
            if((float)$interval == $interval) {
                $seconds = (float)$interval;
            } else if((int)$interval == $interval) {
                $seconds = (int)$interval;
            }
        } else if(is_int($interval) || is_float($interval)) {
            $seconds = $interval;
        } else if(preg_match('/^(([0-9]+)\:)?([0-9]{1,2})\:([0-9.]+)$/', $interval)) {
            $parts = explode(':', $interval);
            $i = 1;
            $seconds = 0;

            while(!empty($parts)) {
                $value = (int)array_pop($parts);
                $seconds += $value * self::$_multipliers[$i++];
            }
        }

        if($seconds !== null) {
            return \DateInterval::createFromDateString((int)$seconds.' seconds');
        }

        $interval = (string)$interval;

        if(substr($interval, 0, 1) == 'P') {
            return new \DateInterval($interval);
        } else {
            return \DateInterval::createFromDateString($interval);
        }
    }
    
    
// Duration
    public function timeSince($date=null) {
        if($date !== null) {
            $time = self::factory($date)->toTimestamp();
        } else {
            $time = time();
        }

        return new Duration($time - $this->toTimestamp());
    }
    
    public function timeUntil($date=null) {
        if($date !== null) {
            $time = self::factory($date)->toTimestamp();
        } else {
            $time = time();
        }

        return new Duration($this->toTimestamp() - $time);
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->format('Y-m-d H:i:s T');
    }
}