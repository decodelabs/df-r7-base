<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IDate extends \Serializable, core\IStringProvider {

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

    // Creation
    public static function fromLocaleString($string, $timezone=true, $size=self::SHORT, $locale=true);
    public static function factory($date, $timezone=null);

    // Time
    public function enableTime();
    public function disableTime();

    // Time zone
    public function toUserTimezone();
    public function toUtc();
    public function toTimezone($timezone);
    public function getTimezone();
    public function getTimezoneAbbreviation();

    // Formatting
    public function toTimestamp();
    public function userLocaleFormat($size='long');
    public function localeFormat($size='long', $locale=true);
    public function userLocaleDateFormat($size='long');
    public function localeDateFormat($size='long', $locale=true);
    public function userLocaleTimeFormat($size='long');
    public function localeTimeFormat($size='long', $locale=true);
    public function userFormat($format='Y-m-d H:i:s T');
    public function format($format='Y-m-d H:i:s T');

    // Comparison
    public function eq($date);
    public function is($date);
    public function isBetween($start, $end);
    public function gt($date);
    public function gte($date);
    public function lt($date);
    public function lte($date);

    public function isPast();
    public function isNearPast($hours=null);
    public function isFuture();
    public function isNearFuture($hours=null);

    public function isYear($year);
    public function getYear();
    public function getShortYear();

    public function isMonth($month);
    public function getMonth();
    public function getMonthName();
    public function getShortMonthName();

    public function isWeek($week);
    public function getWeek();

    public function isDay($day);
    public function getDay();
    public function getDayName();
    public function getShortDayName();
    public function isDayOfWeek($day);
    public function getDayOfWeek();

    public function isHour($hour);
    public function getHour();
    public function isMinute($minute);
    public function getMinute();
    public function isSecond($second);
    public function getSecond();

    // Modification
    public function modify($string);
    public function modifyNew($string);
    public function add($interval);
    public function addNew($interval);
    public function subtract($interval);
    public function subtractNew($interval);

    // Duration
    public function timeSince($date=null);
    public function timeUntil($date=null);
}


interface IDuration extends core\IStringProvider {

// Locale
    public function setLocale($locale);
    public function getLocale();

// Reference date
    public function toDate();
    public function invert();

// Util
    public function isEmpty();
    public function eq($duration);
    public function gt($duration);
    public function gte($duration);
    public function lt($duration);
    public function lte($duration);

// Unit
    public static function fromUnit($value, $unit);
    public function toUnit($unit);
    public static function normalizeUnitId($id);
    public static function getUnitString($unit, $plural=true, $locale=null);

// Microseconds
    public function setMicroseconds($us);
    public function getMicroseconds();
    public function addMicroseconds($us);
    public function subtractMicroseconds($us);

// Milliseconds
    public function setMilliseconds($ms);
    public function getMilliseconds();
    public function addMilliseconds($ms);
    public function subtractMilliseconds($ms);

// Seconds
    public function setSeconds($seconds);
    public function getSeconds();
    public function addSeconds($seconds);
    public function subtractSeconds($seconds);


// Minutes
    public function setMinutes($minutes);
    public function getMinutes();
    public function addMinutes($minutes);
    public function subtractMinutes($minutes);


// Hours
    public function setHours($hours);
    public function getHours();
    public function addHours($hours);
    public function subtractHours($hours);


// Days
    public function setDays($days);
    public function getDays();
    public function addDays($days);
    public function subtractDays($days);


// Weeks
    public function setWeeks($weeks);
    public function getWeeks();
    public function addWeeks($weeks);
    public function subtractWeeks($weeks);


// Months
    public function setMonths($months);
    public function getMonths();
    public function addMonths($months);
    public function subtractMonths($months);


// Years
    public function setYears($years);
    public function getYears();
    public function addYears($years);
    public function subtractYears($years);

    public function getUserString();
    public function getTimeFormatString();
}


interface ITimeOfDay extends core\IStringProvider {
    public function setSeconds($seconds);
    public function setAsSeconds($seconds);
    public function addSeconds($seconds);
    public function subtractSeconds($seconds);
    public function getSeconds();
    public function getAsSeconds();

    public function setMinutes($minutes);
    public function setAsMinutes($minutes);
    public function addMinutes($minutes);
    public function subtractMinutes($minutes);
    public function getMinutes();
    public function getAsMinutes();

    public function setHours($hours);
    public function setAsHours($hours);
    public function addHours($hours);
    public function subtractHours($hours);
    public function getHours();
    public function getAsHours();
}


interface ISchedule extends core\IStringProvider {
    public function setMinute($minute);
    public function getMinute();
    public function setHour($hour);
    public function getHour();
    public function setDay($day);
    public function getDay();
    public function setMonth($month);
    public function getMonth();
    public function setWeekday($weekday);
    public function getWeekday();

    public function getLast($time=null, $yearThreshold=2);
    public function getNext($time=null, $yearThreshold=2);
}