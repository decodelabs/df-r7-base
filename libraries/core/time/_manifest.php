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
    // Creation
    public static function fromLocaleString($string, $timezone=true, $size=self::SHORT, $locale=true);
    public static function factory($date, $timezone=null);
    
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
    public function gt($date);
    public function gte($date);
    public function lt($date);
    public function lte($date);

    public function isPast();
    public function isNearPast($hours=null);
    public function isFuture();
    public function isNearFuture($hours=null);
    
    // Modification
    public function modify($string);
    public function modifyNew($string);
    public function add($duration);
    public function addNew($duration);
    public function subtract($duration);
    public function subtractNew($duration);
    
    // Duration
    public function timeSince();
    public function timeUntil();
}


interface IDuration extends core\IStringProvider {
    
// Locale
    public function setLocale($locale);
    public function getLocale();
    
// Reference date
    public function setReferenceDate(IDate $referenceDate=null);
    public function getReferenceDate();
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
    public static function fromUnit($value, $unit, IDate $referenceDate=null);
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