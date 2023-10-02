<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\time;

use DateTime;
use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

use df\user;

class Date implements IDate, Dumpable
{
    public const MONTHS = [
        'jan' => 1, 'january' => 1,
        'feb' => 2, 'february' => 2,
        'mar' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'may' => 5,
        'jun' => 6, 'june' => 6,
        'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8,
        'sep' => 9, 'september' => 9,
        'oct' => 10, 'october' => 10,
        'nov' => 11, 'november' => 11,
        'dec' => 12, 'december' => 12
    ];

    public const DAYS = [
        'mon' => 1, 'monday' => 1,
        'tue' => 2, 'tuesday' => 2,
        'wed' => 3, 'wednesday' => 3,
        'thu' => 4, 'thursday' => 4,
        'fri' => 5, 'friday' => 5,
        'sat' => 6, 'saturday' => 6,
        'sun' => 7, 'sunday' => 7
    ];

    public $_date;
    protected $_timeEnabled = true;

    public static function fromCompressedString($string, $timezone = true): IDate
    {
        if ($string instanceof IDate) {
            return $string;
        }

        $date = substr($string, 0, 8);
        $date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
        $timeEnabled = false;

        if (strlen((string)$string) == 14) {
            $time = substr($string, 8);
            $time = substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':' . substr($time, 4, 2);
            $date .= ' ' . $time;
            $timeEnabled = true;
        }

        return new self($date, $timezone, $timeEnabled);
    }

    public static function fromLocaleString($string, $timezone = true, $size = self::SHORT, $locale = null): IDate
    {
        if ($string instanceof IDate) {
            return $string;
        }

        $timezone = self::_normalizeTimezone($timezone);
        $locale = (string)core\i18n\Locale::factory($locale);

        $formatter = new \IntlDateFormatter(
            $locale,
            $size,
            $size,
            $timezone
        );

        return new self($formatter->parse($string), $timezone);
    }

    public static function fromFormatString($date, $format, $timezone = true, $locale = null): IDate
    {
        if ($date instanceof IDate) {
            return $date;
        }

        $timezone = self::_normalizeTimezone($timezone);
        $locale = (string)core\i18n\Locale::factory($locale);

        $date = \DateTime::createFromFormat($format, $date, $timezone);
        return new self($date);
    }

    public static function normalize($date, $timezone = null, ?bool $timeEnabled = null): ?IDate
    {
        if (empty($date)) {
            return null;
        } elseif ($date instanceof IDate) {
            return $date;
        }

        return self::factory($date, $timezone, $timeEnabled);
    }

    public static function factory($date, $timezone = null, $timeEnabled = null): IDate
    {
        if ($date instanceof IDuration) {
            $date = '+' . $date->getSeconds() . ' seconds';
        }

        if ($date instanceof IDate) {
            return clone $date;
        }

        return new self($date, $timezone, $timeEnabled);
    }

    private static function _normalizeTimezone($timezone)
    {
        if ($timezone === false) {
            $timezone = null;
        } elseif ($timezone === true) {
            $userManager = user\Manager::getInstance();
            $timezone = $userManager->getClient()->getTimezone();
        }

        if ($timezone === null) {
            $timezone = 'UTC';
        }

        if (!$timezone instanceof \DateTimeZone) {
            try {
                $timezone = new \DateTimeZone((string)$timezone);
            } catch (\Throwable $e) {
                throw Exceptional::InvalidArgument(
                    $e->getMessage()
                );
            }
        }

        return $timezone;
    }

    public function __construct($date = null, $timezone = null, $timeEnabled = null)
    {
        if ($date instanceof self) {
            if ($timeEnabled === null) {
                $timeEnabled = $date->_timeEnabled;
            }

            $this->_setDate(clone $date->_date, $timeEnabled);
            return;
        } elseif ($date instanceof \DateTime) {
            $this->_setDate($date, $timeEnabled);
            return;
        }

        $timestamp = null;

        if (is_numeric($date)) {
            $timestamp = $date;
            $date = 'now';
        }

        $timezone = self::_normalizeTimezone($timezone);

        if ($date === null) {
            $date = 'now';
        }

        try {
            $this->_setDate(new \DateTime($date, $timezone), $timeEnabled);
        } catch (\Throwable $e) {
            throw Exceptional::InvalidArgument(
                $e->getMessage()
            );
        }

        if ($timestamp !== null) {
            $this->_date->setTimestamp($timestamp);
        }
    }

    protected function _setDate(\DateTime $date, $timeEnabled)
    {
        $this->_date = $date;

        if ($timeEnabled !== null) {
            $this->_timeEnabled = null;

            if (!$timeEnabled) {
                $this->disableTime();
            } else {
                $this->enableTime();
            }
        }
    }


    public function getRaw(): DateTime
    {
        return $this->_date;
    }


    // Serialize
    public function serialize()
    {
        return $this->__toString();
    }

    public function __serialize(): array
    {
        return [$this->__toString()];
    }

    public function unserialize(string $string): void
    {
        $this->_date = new \DateTime($string);
    }

    public function __unserialize(array $data): void
    {
        $this->_date = new \DateTime($data[0]);
    }


    // Duplicate
    public function __clone()
    {
        $this->_date = clone $this->_date;
    }


    // Time
    public function enableTime()
    {
        $this->_timeEnabled = true;
        return $this;
    }

    public function disableTime()
    {
        if ($this->_timeEnabled === false) {
            return $this;
        }

        if ($this->getTimezone() != 'UTC') {
            $this->_date = new \DateTime($this->_date->format('Y-m-d'), new \DateTimeZone('UTC'));
        } else {
            $this->_date->modify('midnight');
        }

        $this->_timeEnabled = false;
        return $this;
    }

    public function hasTime()
    {
        return $this->_timeEnabled;
    }


    // Time zone
    private static $_userTimezone;

    public function toUserTimezone()
    {
        if (!$this->_timeEnabled) {
            return $this;
        }

        if (self::$_userTimezone === null) {
            try {
                $client = $userManager = user\Manager::getInstance()->getClient();
                self::$_userTimezone = new \DateTimeZone($client->getTimezone());
            } catch (\Throwable $e) {
                self::$_userTimezone = new \DateTimeZone('UTC');
            }
        }

        $this->_date->setTimezone(self::$_userTimezone);
        return $this;
    }

    public function toUtc()
    {
        if (!$this->_timeEnabled) {
            return $this;
        }

        $this->_date->setTimezone(new \DateTimeZone('UTC'));
        return $this;
    }

    public function toTimezone($timezone)
    {
        if (!$this->_timeEnabled) {
            return $this;
        }

        if (!$timezone instanceof \DateTimeZone) {
            try {
                $timezone = new \DateTimeZone((string)$timezone);
            } catch (\Throwable $e) {
                throw Exceptional::InvalidArgument(
                    $e->getMessage()
                );
            }
        }

        $this->_date->setTimezone($timezone);

        return $this;
    }

    public function getTimezone()
    {
        return $this->_date->getTimezone()->getName();
    }

    public function getTimezoneAbbreviation()
    {
        return $this->_date->format('T');
    }


    // Formatting
    public function toString(): string
    {
        if ($this->_timeEnabled) {
            return $this->format('c');
        } else {
            return $this->format('Y-m-d');
        }
    }

    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            return '0000-00-00';
        }
    }

    public function toTimestamp()
    {
        return $this->_date->getTimestamp();
    }

    public function localeFormat($size = self::LONG, $locale = null)
    {
        if (!$this->_timeEnabled) {
            return $this->localeDateFormat($size, $locale);
        }

        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);

        $output = \IntlDateFormatter::create($locale, $size, $size, $this->_date->getTimezone());

        if ($output) {
            return $output->format($this->toTimestamp());
        } else {
            return $this->format('Y-m-d H:i:s');
        }
    }

    public function localeDateFormat($size = 'long', $locale = true)
    {
        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);

        $formatter = \IntlDateFormatter::create($locale, $size, \IntlDateFormatter::NONE, $this->_date->getTimezone());

        if (!$formatter) {
            throw Exceptional::Runtime(
                'Unable to create IntlDateFormatter',
                null,
                $locale
            );
        }

        return $formatter->format($this->toTimestamp());
    }

    public function localeTimeFormat($size = 'long', $locale = true)
    {
        $locale = (string)core\i18n\Locale::factory($locale);
        $size = $this->_normalizeFormatterSize($size);

        $formatter = \IntlDateFormatter::create($locale, \IntlDateFormatter::NONE, $size, $this->_date->getTimezone());

        if (!$formatter) {
            throw Exceptional::Runtime(
                'Unable to create IntlDateFormatter',
                null,
                $locale
            );
        }

        return $formatter->format($this->toTimestamp());
    }

    protected function _normalizeFormatterSize($size)
    {
        if (is_string($size)) {
            $size = strtolower($size);

            switch ($size) {
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

        switch ($size) {
            case self::FULL:
            case self::LONG:
            case self::MEDIUM:
                return $size;

            case self::SHORT:
            default:
                return self::SHORT;
        }
    }

    public function format($format = 'Y-m-d H:i:s T')
    {
        return $this->_date->format($format);
    }


    // Comparison
    public function eq($date)
    {
        if ($date === null) {
            return false;
        }

        $date = self::factory($date, null, $this->_timeEnabled);
        return $this->toTimestamp() == $date->toTimestamp();
    }

    public function is($date)
    {
        if ($date === null) {
            return false;
        }

        return $this->format('Y-m-d') == self::factory($date)->format('Y-m-d');
    }

    public function isBetween($start, $end)
    {
        if ($start === null && $end === null) {
            return false;
        }

        if ($start !== null && !$this->gte($start)) {
            return false;
        }

        if ($end !== null && !$this->lt($end)) {
            return false;
        }

        return true;
    }

    public function gt($date)
    {
        if ($date === null) {
            return true;
        }

        return $this->toTimestamp() > self::factory($date)->toTimestamp();
    }

    public function gte($date)
    {
        if ($date === null) {
            return true;
        }

        return $this->toTimestamp() >= self::factory($date)->toTimestamp();
    }

    public function lt($date)
    {
        if ($date === null) {
            return false;
        }

        return $this->toTimestamp() < self::factory($date)->toTimestamp();
    }

    public function lte($date)
    {
        if ($date === null) {
            return false;
        }

        return $this->toTimestamp() <= self::factory($date)->toTimestamp();
    }

    public function isPast()
    {
        return $this->toTimestamp() <= time();
    }

    public function isNearPast($hours = null)
    {
        if (empty($hours)) {
            $hours = 24;
        }

        $ts = $this->toTimestamp();
        $time = time();

        return $ts <= $time && $ts > $time - ($hours * 60);
    }

    public function isFuture()
    {
        return $this->toTimestamp() > time();
    }

    public function isNearFuture($hours = null)
    {
        if (empty($hours)) {
            $hours = 24;
        }

        $ts = $this->toTimestamp();
        $time = time();

        return $ts > $time && $ts < $time + ($hours * 60);
    }

    public function isToday($date = null)
    {
        return $this->format('Y-m-d') == self::factory($date ?? 'today')->format('Y-m-d');
    }



    public function isYear($year)
    {
        if ($this->format('Y') == $year) {
            return true;
        }

        if ($year < 100 && strlen((string)$year) == 2 && $this->format('y') == $year) {
            return true;
        }

        return false;
    }

    public function getYear()
    {
        return (int)$this->format('Y');
    }

    public function getShortYear()
    {
        return $this->format('y');
    }

    public function isMonth($month)
    {
        if (!is_numeric($month)) {
            $month = strtolower((string)$month);

            if (!isset(self::MONTHS[$month])) {
                throw Exceptional::InvalidArgument(
                    $month . ' is not a valid month string'
                );
            }

            $month = self::MONTHS[$month];
        }

        return $this->format('n') == $month;
    }

    public function getMonth()
    {
        return (int)$this->format('n');
    }

    public function getMonthName()
    {
        return $this->format('F');
    }

    public function getShortMonthName()
    {
        return $this->format('M');
    }


    public function isWeek($week)
    {
        return $this->format('W') == $week;
    }

    public function getWeek()
    {
        return (int)$this->format('W');
    }

    public function isDay($day)
    {
        if (!is_numeric($day)) {
            return $this->isDayOfWeek($day);
        }

        return $this->format('j') == $day;
    }

    public function getDay()
    {
        return (int)$this->format('j');
    }

    public function getDayName()
    {
        return $this->format('l');
    }

    public function getShortDayName()
    {
        return $this->format('D');
    }

    public function isDayOfWeek($day)
    {
        if (!is_numeric($day)) {
            $day = strtolower((string)$day);

            if (!isset(self::DAYS[$day])) {
                throw Exceptional::InvalidArgument(
                    $day . ' is not a valid day string'
                );
            }

            $day = self::DAYS[$day];
        }

        if ($day == 0) {
            $day = 7;
        }

        return $this->format('N') == $day;
    }

    public function getDayOfWeek()
    {
        return (int)$this->format('N');
    }

    public function isHour($hour)
    {
        return $this->format('G') == $hour;
    }

    public function getHour()
    {
        return (int)$this->format('G');
    }

    public function isMinute($minute)
    {
        return $this->format('i') == $minute;
    }

    public function getMinute()
    {
        return (int)$this->format('i');
    }

    public function isSecond($second)
    {
        return $this->format('s') == $second;
    }

    public function getSecond()
    {
        return (int)$this->format('s');
    }



    // Modification
    public function modify($string)
    {
        $this->_date->modify($string);

        if (!$this->_timeEnabled) {
            $this->_date->modify('midnight');
        }

        return $this;
    }

    public function modifyNew($string)
    {
        $output = clone $this;
        return $output->modify($string);
    }

    public function add($interval)
    {
        $interval = $this->_normalizeInterval($interval);
        $this->_date->add($interval);

        if (!$this->_timeEnabled) {
            $this->_date->modify('midnight');
        }

        return $this;
    }

    public function addNew($interval)
    {
        $output = clone $this;
        return $output->add($interval);
    }

    public function subtract($interval)
    {
        $interval = $this->_normalizeInterval($interval);
        $this->_date->sub($interval);

        if (!$this->_timeEnabled) {
            $this->_date->modify('midnight');
        }

        return $this;
    }

    public function subtractNew($interval)
    {
        $output = clone $this;
        return $output->subtract($interval);
    }

    protected function _normalizeInterval($interval)
    {
        $seconds = null;

        if ($interval instanceof IDuration) {
            $seconds = $interval->getSeconds();
        } elseif (is_int($interval) || is_float($interval)) {
            $seconds = $interval;
        } elseif (is_numeric($interval)) {
            if ((float)$interval == $interval) {
                $seconds = (float)$interval;
            } elseif ((int)$interval == $interval) {
                $seconds = (int)$interval;
            }
        } elseif (preg_match('/^(([0-9]+)\:)?([0-9]{1,2})\:([0-9.]+)$/', $interval)) {
            $parts = explode(':', $interval);
            $i = 1;
            $seconds = 0;

            while (!empty($parts)) {
                $value = (int)array_pop($parts);
                $seconds += $value * Duration::MULTIPLIERS[$i++];
            }
        }

        if ($seconds !== null) {
            return \DateInterval::createFromDateString((int)$seconds . ' seconds');
        }

        $interval = (string)$interval;

        if (substr($interval, 0, 1) == 'P') {
            return new \DateInterval($interval);
        } else {
            return \DateInterval::createFromDateString($interval);
        }
    }


    // Duration
    public function timeSince($date = null)
    {
        if ($date !== null) {
            $time = self::factory($date)->toTimestamp();
            return new Duration($this->toTimestamp() - $time);
        } else {
            return new Duration(time() - $this->toTimestamp());
        }
    }

    public function timeUntil($date = null)
    {
        if ($date !== null) {
            $time = self::factory($date)->toTimestamp();
            return new Duration($time - $this->toTimestamp());
        } else {
            return new Duration($this->toTimestamp() - time());
        }
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_timeEnabled) {
            yield 'definition' => $this->format('Y-m-d H:i:s T');
        } else {
            yield 'definition' => $this->format('Y-m-d');
        }
    }
}
