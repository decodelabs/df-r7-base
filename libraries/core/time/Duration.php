<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;

class Duration implements IDuration, core\IDumpable {
    
    const SECONDS = 1;
    const MINUTES = 2;
    const HOURS = 3;
    const DAYS = 4;
    const WEEKS = 5;
    const MONTHS = 6;
    const YEARS = 7;
    
    protected $_seconds = 0;
    protected $_referenceDate = null;
    protected $_locale = null;
    
    public static function factory($time, IDate $referenceDate=null) {
        if($time instanceof IDuration) {
            return $time;
        }
        
        return new self($time, $referenceDate);
    }

    public static function fromUnit($value, $unit, IDate $referenceDate=null) {
        if($value instanceof IDuration) {
            return $value;
        }

        $unit = self::normalizeUnitId($unit);

        switch($unit) {
            case self::SECONDS:
                return self::fromSeconds($value, $referenceDate);

            case self::MINUTES:
                return self::fromMinutes($value, $referenceDate);

            case self::HOURS:
                return self::fromHours($value, $referenceDate);

            case self::DAYS:
                return self::fromDays($value, $referenceDate);

            case self::WEEKS:
                return self::fromWeeks($value, $referenceDate);

            case self::MONTHS:
                return self::fromMonths($value, $referenceDate);

            case self::YEARS:
                return self::fromYears($value, $referenceDate);
        }
    }
    
    public static function fromSeconds($seconds, IDate $referenceDate=null) {
        return new self($seconds, $referenceDate);
    }
    
    public static function fromMinutes($minutes, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setMinutes($minutes);
    }
    
    public static function fromHours($hours, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setHours($hours);
    }
    
    public static function fromDays($days, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setDays($days);
    }
    
    public static function fromWeeks($weeks, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setWeeks($weeks);
    }
    
    public static function fromMonths($months, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setMonths($months);
    }
    
    public static function fromYears($years, IDate $referenceDate=null) {
        $output = new self(0, $referenceDate);
        return $output->setYears($years);
    }
    
    public function __construct($time=0, IDate $referenceDate=null) {
        if($time instanceof IDuration) {
            $time = $time->getSeconds();
        }

        if(is_string($time)) {
            $time = $this->_parseTime($time);
        }
        
        $this->setSeconds($time);
        $this->setReferenceDate($referenceDate);
    }
    
    protected function _parseTime($time) {
        if((float)$time == $time) {
            return (float)$time;
        }

        if((int)$time == $time) {
            return (int)$time;
        }

        core\stub($time);
    }
    
// Reference date
    public function setReferenceDate(IDate $referenceDate=null) {
        $this->_referenceDate = $referenceDate;
        return $this;
    }
    
    public function getReferenceDate() {
        return $this->_referenceDate;
    }
    
    public function toDate() {
        if($this->_referenceDate) {
            $reference = $this->_referenceDate->toTimestamp();
        } else {
            $reference = time();
        }
        
        return new Date((int)($reference + $this->_seconds));
    }
    
    public function invert() {
        $this->_seconds *= -1;
        return $this;
    }


// Util
    public function isEmpty() {
        return $this->_seconds == 0;
    }

    public function eq($duration) {
        return $this->_seconds == self::factory($duration)->getSeconds();
    }

    public function gt($duration) {
        return $this->_seconds > self::factory($duration)->getSeconds();
    }
    
    public function gte($duration) {
        return $this->_seconds >= self::factory($duration)->getSeconds();
    }
    
    public function lt($duration) {
        return $this->_seconds < self::factory($duration)->getSeconds();
    }
    
    public function lte($duration) {
        return $this->_seconds <= self::factory($duration)->getSeconds();
    }
    
    
// Microseconds
    public function setMicroseconds($us) {
        $this->_seconds = $us / 1000000;
        return $this;
    }
    
    public function getMicroseconds() {
        return $this->_seconds * 1000000;
    }
    
    public function addMicroseconds($us) {
        $this->_seconds += $us / 1000000;
        return $this;
    }
    
    public function subtractMicroseconds($us) {
        $this->_seconds -= $us / 1000000;
        return $this;
    }
    

// Milliseconds
    public function setMilliseconds($ms) {
        $this->_seconds = $ms / 1000;
        return $this;
    }
    
    public function getMilliseconds() {
        return $this->_seconds * 1000;
    }
    
    public function addMilliseconds($ms) {
        $this->_seconds += $ms / 1000;
        return $this;
    }
    
    public function subtractMilliseconds($ms) {
        $this->_seconds -= $ms / 1000;
        return $this;
    }
    
    
// Seconds
    public function setSeconds($seconds) {
        $this->_seconds = $seconds;
        return $this;
    }
    
    public function getSeconds() {
        return $this->_seconds;
    }
    
    public function addSeconds($seconds) {
        $this->_seconds += $seconds;
        return $this;
    }
    
    public function subtractSeconds($seconds) {
        $this->_seconds -= $seconds;
        return $this;
    }
    
    
// Minutes
    public function setMinutes($minutes) {
        $this->_seconds = $minutes * 60;
        return $this;
    }
    
    public function getMinutes() {
        return $this->_seconds / 60;
    }
    
    public function addMinutes($minutes) {
        $this->_seconds += $minutes * 60;
        return $this;
    }
    
    public function subtractMinutes($minutes) {
        $this->_seconds -= $minutes * 60;
        return $this;
    }
    
    
// Hours
    public function setHours($hours) {
        $this->_seconds = $hours * 3600;
        return $this;
    }
    
    public function getHours() {
        return $this->_seconds / 3600;
    }
    
    public function addHours($hours) {
        $this->_seconds += $hours * 3600;
        return $this;
    }
    
    public function subtractHours($hours) {
        $this->_seconds -= $hours * 3600;
        return $this;
    }
    
    
// Days
    public function setDays($days) {
        $this->_seconds = $days * 86400;
        return $this;
    }
    
    public function getDays() {
        return $this->_seconds / 86400;
    }
    
    public function addDays($days) {
        $this->_seconds += $days * 86400;
        return $this;
    }
    
    public function subtractDays($days) {
        $this->_seconds -= $days * 86400;
        return $this;
    }
    
    
// Weeks
    public function setWeeks($weeks) {
        $this->_seconds = $weeks * 604800;
        return $this;
    }
    
    public function getWeeks() {
        return $this->_seconds / 604800;
    }
    
    public function addWeeks($weeks) {
        $this->_seconds += $weeks * 604800;
        return $this;
    }
    
    public function subtractWeeks($weeks) {
        $this->_seconds -= $weeks * 604800;
        return $this;
    }
    
    
// Months
    public function setMonths($months) {
        if(!$this->_referenceDate) {
            $this->_seconds = $months * 2592000;
        } else if($months == 0) {
            $this->_seconds = 0;
            return $this;
        } else {
            $seconds = 0;
            $startMonth = $currentMonth = $this->_referenceDate->format('n');
            $startYear = $currentYear = $this->_referenceDate->format('Y');
            $isLeapYear = Date::isLeapYear($currentYear);
            $isNegative = false;
            
            if($months < 0) {
                $months *= -1;
                $isNegative = true;
            }
            
            while(true) {
                $monthAmount = 1;
                $monthDays = Date::monthDays($currentMonth, $isLeapYear);
                $totalMonthSeconds = $monthSeconds = $monthDays * 86400;
                
                if($startYear == $currentYear && $currentMonth == $startMonth) {
                    $monthBuffer = $this->_referenceDate->toTimestamp() - Date::factory($startYear.'-'.$startMonth.'-1')->toTimestamp();
                    $monthSeconds -= $monthBuffer;
                    $monthAmount = $monthSeconds / $totalMonthSeconds;
                }
                
                $months -= $monthAmount;
                
                if($months < 0) {
                    $monthSeconds += $months * $totalMonthSeconds; 
                    $seconds += $monthSeconds;
                    break;
                } else {
                    $seconds += $monthSeconds;
                }
                
                $currentMonth++;
                
                if($currentMonth == 13) {
                    $currentMonth = 1;
                    $currentYear++;
                }
            }
            
            $seconds -= 86400;
            
            if($isNegative) {
                $seconds *= -1;
            }
            
            $this->_seconds = $seconds;
        }
        
        return $this;
    }
    
    public function getMonths() {
        if(!$this->_referenceDate) {
            return $this->_seconds / 2592000;
        } else {
            $seconds = $this->_seconds;
            
            if($seconds == 0) {
                return 0;
            }
            
            $startMonth = $currentMonth = $this->_referenceDate->format('n');
            $startYear = $currentYear = $this->_referenceDate->format('Y');
            $isLeapYear = Date::isLeapYear($currentYear);
            $isNegative = false;
            
            if($seconds < 0) {
                $isNegative = true;
                $seconds *= -1;
            }
            
            $months = 0;
            
            while(true) {
                $monthAmount = 1;
                $monthDays = Date::monthDays($currentMonth, $isLeapYear);
                $totalMonthSeconds = $monthSeconds = $monthDays * 86400;
                
                if($startYear == $currentYear && $currentMonth == $startMonth) {
                    $monthBuffer = $this->_referenceDate->toTimestamp() - Date::factory($startYear.'-'.$startMonth.'-1')->toTimestamp();
                    $monthSeconds -= $monthBuffer;
                    $monthAmount = $monthSeconds / $totalMonthSeconds;
                }
                
                $seconds -= $monthSeconds;
                
                if($seconds < 0) {
                    if($startYear == $currentYear && $currentMonth == $startMonth) {
                        $months = ($seconds + $monthSeconds) / $totalMonthSeconds;
                    } else {
                        $overflow = $monthSeconds + $seconds;
                        $monthAmount -= ($totalMonthSeconds - $overflow) / $totalMonthSeconds;
                        $months += $monthAmount;
                    }
                    
                    break;
                } else {
                    $months += $monthAmount;
                }
                
                $currentMonth++;
                
                if($currentMonth == 13) {
                    $currentMonth = 1;
                    $currentYear++;
                }
            }
            
            // Smooth off rounding errors
            $months = round($months, 2);
            
            if($isNegative) {
                $months *= -1;
            }
            
            return $months;
        }
    }
    
    public function addMonths($months) {
        if(!$this->_referenceDate) {
            $this->_seconds += $months * 2592000;
            return $this;
        }
        
        $temp = new self(0, $this->toDate());
        $temp->setMonths($months);
        
        $this->_seconds += $temp->_seconds;
        return $this;
    }
    
    public function subtractMonths($months) {
        if(!$this->_referenceDate) {
            $this->_seconds -= $months * 2592000;
            return $this;
        }
        
        $temp = new self(0, $this->toDate());
        $temp->setMonths(-$months);
        
        $this->_seconds += $temp->_seconds;
        return $this;
    }
    
    
// Years
    public function setYears($years) {
        $this->_seconds = $years * 31449600;
        return $this;
    }
    
    public function getYears() {
        return $this->_seconds / 31449600;
    }
    
    public function addYears($years) {
        $this->_seconds += $years * 31449600;
        return $this;
    }
    
    public function subtractYears($years) {
        $this->_seconds -= $years * 31449600;
        return $this;
    }
    
    
// Locale
    public function setLocale($locale) {
        if($locale === null) {
            $this->_locale = null;
        } else {
            $this->_locale = core\i18n\Locale::factory($locale);
        }
        
        return $this;
    }
    
    public function getLocale() {
        return $this->_locale;
    }
    
// Format
    public static function normalizeUnitId($id) {
        if(is_string($id)) {
            switch(strtolower($id)) {
                case 'second':
                case 'seconds':
                    $id = 1;
                    break;

                case 'minute':
                case 'minutes':
                    $id = 2;
                    break;

                case 'hour':
                case 'hours':
                    $id = 3;
                    break;

                case 'day':
                case 'days':
                    $id = 4;
                    break;

                case 'week':
                case 'weeks':
                    $id = 5;
                    break;

                case 'month':
                case 'months':
                    $id = 6;
                    break;

                case 'year':
                case 'years':
                    $id = 7;
                    break;
            }
        }

        $id = (int)$id;

        if($id < 1) {
            $id = 1;
        }

        if($id > 7) {
            $id = 7;
        }

        return $id;
    }

    public static function getUnitString($unit, $plural=true, $locale=null) {
        $unit = self::normalizeUnitId($unit);
        $translator = core\i18n\translate\Handler::factory('core/time/Duration', $locale);

        switch($unit) {
            case self::SECONDS:
                return $translator->_([0 => 'second', 1 => 'seconds'], null, (int)$plural);

            case self::MINUTES:
                return $translator->_([0 => 'minute', 1 => 'minutes'], null, (int)$plural);

            case self::HOURS:
                return $translator->_([0 => 'hour', 1 => 'hours'], null, (int)$plural);

            case self::DAYS:
                return $translator->_([0 => 'day', 1 => 'days'], null, (int)$plural);

            case self::WEEKS:
                return $translator->_([0 => 'week', 1 => 'weeks'], null, (int)$plural);

            case self::MONTHS:
                return $translator->_([0 => 'month', 1 => 'months'], null, (int)$plural);

            case self::YEARS:
                return $translator->_([0 => 'year', 1 => 'years'], null, (int)$plural);
        }
    }

    public function toString($maxUnits=1, $shortUnits=false, $maxUnit=self::YEARS) {
        $translator = core\i18n\translate\Handler::factory('core/time/Duration', $this->_locale);
        $seconds = $this->_seconds;
        $isNegative = false;
        
        if($seconds < 0) {
            $seconds *= -1;
            $isNegative = true;
        }
        
        $maxUnit = self::normalizeUnitId($maxUnit);
        $output = $this->_createOutputArray($seconds, $maxUnits, $maxUnit);
        
        foreach($output as $unit => $value) {
            if($isNegative) {
                $value *= -1;
            }
            
            $round = 1;
            
            if($unit == self::SECONDS && $maxUnit == self::SECONDS) {
                $round = 3;
            }
            
            $output[$unit] = $this->_addUnitString($translator, round($value, $round), $unit, $shortUnits);
        }
        
        return implode(', ', $output);
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }

    public function toUnit($unit) {
        $unit = self::normalizeUnitId($unit);

        switch($unit) {
            case self::SECONDS:
                return $this->getSeconds();

            case self::MINUTES:
                return $this->getMinutes();

            case self::HOURS:
                return $this->getHours();

            case self::DAYS:
                return $this->getDays();

            case self::WEEKS:
                return $this->getWeeks();

            case self::MONTHS:
                return $this->getMonths();

            case self::YEARS:
                return $this->getYears();
        }
    }
    
    private function _createOutputArray($seconds, $maxUnits, $maxUnit) {
        if($maxUnits <= 0) {
            $maxUnits = 1;
        }
        
        $output = array();
        $units = 0;
        
        // Years
        if($maxUnit >= self::YEARS && $seconds >= 31449600) {
            $output[self::YEARS] = floor($seconds / 31449600);
            $seconds %= 31449600;
            $units++;
            
            if($units >= $maxUnits || !$seconds) {
                if($seconds) {
                    $output[self::YEARS] += $seconds / 31449600;
                }
                
                return $output;
            }
        }
        
        
        // Months
        if($maxUnit >= self::MONTHS && $seconds > 2592000) {
            if($this->_referenceDate) {
                $temp = $this->_seconds;
                $this->_seconds = $seconds;
                $months = $this->getMonths();
                $this->_seconds = $temp;
                
                $output[self::MONTHS] = floor($months);
                $overflow = $months - $output[self::MONTHS];
                $seconds -= $seconds * ($output[self::MONTHS] / $months);
                $units++;
            } else if($seconds >= 3 * 2592000) {
                $output[self::MONTHS] = floor($seconds / 2592000);
                $seconds %= 2592000;
                $units++;
            }
            
            if($units >= $maxUnits || !$seconds) {
                if($this->_referenceDate) {
                    $output[self::MONTHS] += $overflow;
                } else {
                    if($seconds) {
                        $output[self::MONTHS] += $seconds / 2592000;
                    }
                }
                
                return $output;
            }
        }

        
        // Weeks
        if($maxUnit >= self::WEEKS && $seconds >= 604800) {
            $output[self::WEEKS] = floor($seconds / 604800);
            $seconds %= 604800;
            $units++;
            
            if($units >= $maxUnits || !$seconds) {
                if($seconds) {
                    $output[self::WEEKS] += $seconds / 604800;
                }
                
                return $output;
            }
        }
        
        
        // Days
        if($maxUnit >= self::DAYS && $seconds >= 86400) {
            $output[self::DAYS] = floor($seconds / 86400);
            $seconds %= 86400;
            $units++;
            
            if($units >= $maxUnits || !$seconds) {
                if($seconds) {
                    $output[self::DAYS] += $seconds / 86400;
                }
                
                return $output;
            }
        }
        
        // Hours
        if($maxUnit >= self::HOURS && $seconds >= 3600) {
            $output[self::HOURS] = floor($seconds / 3600);
            $seconds %= 3600;
            $units++;
            
            if($units >= $maxUnits || !$seconds) {
                if($seconds) {
                    $output[self::HOURS] += $seconds / 3600;
                }
                
                return $output;
            }
        }
        
        // Minutes
        if($maxUnit >= self::MINUTES && $seconds >= 60) {
            $output[self::MINUTES] = floor($seconds / 60);
            $seconds %= 60;
            $units++;
            
            if($units >= $maxUnits || !$seconds) {
                /*
                if($seconds) {
                    $output[self::MINUTES] += $seconds / 60;
                }
                */
               
                return $output;
            }
        }
        
        
        // Seconds
        if($maxUnit >= self::SECONDS) {
            $output[self::SECONDS] = $seconds;
        }
        
        return $output;
    }
    
    protected function _addUnitString(core\i18n\translate\IHandler $translator, $number, $unit, $shortUnits=false) {
        switch($unit) {
            case self::SECONDS: 
                if($shortUnits) {
                    return $translator->_(
                        '%n% sc',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% second',
                            '*' => '%n% seconds'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::MINUTES: 
                if($shortUnits) {
                    return $translator->_(
                        '%n% mn',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% minute',
                            '*' => '%n% minutes'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::HOURS: 
                if($shortUnits) {
                    return $translator->_(
                        '%n% hr',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% hour',
                            '*' => '%n% hours'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::DAYS:  
                if($shortUnits) {
                    return $translator->_(
                        '%n% dy',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% day',
                            '*' => '%n% days'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::WEEKS:   
                if($shortUnits) {
                    return $translator->_(
                        '%n% wk',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% week',
                            '*' => '%n% weeks'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::MONTHS:   
                if($shortUnits) {
                    return $translator->_(
                        '%n% mo',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% month',
                            '*' => '%n% months'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            case self::YEARS:   
                if($shortUnits) {
                    return $translator->_(
                        '%n% yr',
                        array('%n%' => $number)
                    );
                } else {
                    return $translator->_(
                        array(
                            'n = 1 || n = -1' => '%n% year',
                            '*' => '%n% years'
                        ),
                        array('%n%' => $number),
                        $number
                    );
                }
                
            default:            
                return $number;
        }
    }


// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}