<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\time;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class Duration implements IDuration, Dumpable
{
    public const MICROSECONDS = -2;
    public const MILLISECONDS = -1;
    public const SECONDS = 1;
    public const MINUTES = 2;
    public const HOURS = 3;
    public const DAYS = 4;
    public const WEEKS = 5;
    public const MONTHS = 6;
    public const YEARS = 7;

    public const MULTIPLIERS = [
        self::MICROSECONDS => 0.000001,
        self::MILLISECONDS => 0.001,
        self::SECONDS => 1,
        self::MINUTES => 60,
        self::HOURS => 3600,
        self::DAYS => 86400,
        self::WEEKS => 604800,
        self::MONTHS => 2635200, // 30.5 days :)
        self::YEARS => 31449600
    ];

    protected $_seconds = 0;
    protected $_locale = null;

    public static function factory($time)
    {
        if ($time instanceof IDuration) {
            return $time;
        }

        return new self($time);
    }

    public static function fromUnit($value, $unit)
    {
        if ($value instanceof IDuration) {
            return $value;
        }

        $unit = self::normalizeUnitId($unit);

        switch ($unit) {
            case self::MICROSECONDS:
                return self::fromMicroseconds($value);

            case self::MILLISECONDS:
                return self::fromMilliseconds($value);

            case self::SECONDS:
                return self::fromSeconds($value);

            case self::MINUTES:
                return self::fromMinutes($value);

            case self::HOURS:
                return self::fromHours($value);

            case self::DAYS:
                return self::fromDays($value);

            case self::WEEKS:
                return self::fromWeeks($value);

            case self::MONTHS:
                return self::fromMonths($value);

            case self::YEARS:
                return self::fromYears($value);
        }
    }

    public static function fromMicroseconds($microseconds)
    {
        return (new self(0))->setMicroseconds($microseconds);
    }

    public static function fromMilliseconds($milliseconds)
    {
        return (new self(0))->setMilliseconds($milliseconds);
    }

    public static function fromSeconds($seconds)
    {
        return new self($seconds);
    }

    public static function fromMinutes($minutes)
    {
        return (new self(0))->setMinutes($minutes);
    }

    public static function fromHours($hours)
    {
        return (new self(0))->setHours($hours);
    }

    public static function fromDays($days)
    {
        return (new self(0))->setDays($days);
    }

    public static function fromWeeks($weeks)
    {
        return (new self(0))->setWeeks($weeks);
    }

    public static function fromMonths($months)
    {
        return (new self(0))->setMonths($months);
    }

    public static function fromYears($years)
    {
        return (new self(0))->setYears($years);
    }

    public static function getUnitList($locale = null)
    {
        return [
            self::MICROSECONDS => self::getUnitString(self::MICROSECONDS, 1, $locale),
            self::MILLISECONDS => self::getUnitString(self::MILLISECONDS, 1, $locale),
            self::SECONDS => self::getUnitString(self::SECONDS, 1, $locale),
            self::MINUTES => self::getUnitString(self::MINUTES, 1, $locale),
            self::HOURS => self::getUnitString(self::HOURS, 1, $locale),
            self::DAYS => self::getUnitString(self::DAYS, 1, $locale),
            self::WEEKS => self::getUnitString(self::WEEKS, 1, $locale),
            self::MONTHS => self::getUnitString(self::MONTHS, 1, $locale),
            self::YEARS => self::getUnitString(self::YEARS, 1, $locale),
        ];
    }

    public function __construct($time = 0)
    {
        if ($time instanceof IDuration) {
            $time = $time->getSeconds();
        } elseif ($time instanceof \DateInterval) {
            $time = $this->_extractInterval($time);
        }

        if (is_string($time)) {
            $time = $this->_parseTime($time);
        }

        $this->setSeconds($time);
    }

    protected function _parseTime($time)
    {
        if (is_numeric($time)) {
            if ((float)$time == $time) {
                return (float)$time;
            }

            if ((int)$time == $time) {
                return (int)$time;
            }
        }

        $time = trim($time);

        if (preg_match('/^(([0-9]+)\:)?([0-9]{1,2})\:([0-9.]+)$/', $time)) {
            $parts = explode(':', $time);
            $i = 1;
            $seconds = 0;

            while (!empty($parts)) {
                $value = array_pop($parts);
                $value = $i == 1 ? (float)$value : (int)$value;
                $seconds += $value * self::MULTIPLIERS[$i++];
            }

            return $seconds;
        }

        if (substr($time, 0, 1) == 'P') {
            $interval = new \DateInterval($time);
        } else {
            $interval = \DateInterval::createFromDateString($time);

            if ($interval->y == 0
            && $interval->m == 0
            && $interval->d == 0
            && $interval->h == 0
            && $interval->i == 0
            && $interval->s == 0
            && false === strpos($time, '0')) {
                throw Exceptional::InvalidArgument(
                    'Invalid duration string: ' . $time
                );
            }
        }

        return $this->_extractInterval($interval);
    }

    protected function _extractInterval(\DateInterval $interval)
    {
        if ($interval->days !== false && $interval->days !== -9999) {
            return $interval->days * self::MULTIPLIERS[self::DAYS];
        }

        $parts = [
            $interval->y,
            $interval->m,
            0,
            $interval->d,
            $interval->h,
            $interval->i,
            $interval->s,
        ];

        $output = 0;

        for ($i = self::YEARS; $i > 0; $i--) {
            $value = (int)array_shift($parts);
            $output += $value * self::MULTIPLIERS[$i];
        }

        return $output;
    }


    public function toDate()
    {
        return new Date(time() + (int)$this->_seconds);
    }

    public function invert()
    {
        $this->_seconds *= -1;
        return $this;
    }


    // Util
    public function isEmpty(): bool
    {
        return $this->_seconds == 0;
    }

    public function eq($duration)
    {
        return $this->_seconds == self::factory($duration)->_seconds;
    }

    public function gt($duration)
    {
        return $this->_seconds > self::factory($duration)->_seconds;
    }

    public function gte($duration)
    {
        return $this->_seconds >= self::factory($duration)->_seconds;
    }

    public function lt($duration)
    {
        return $this->_seconds < self::factory($duration)->_seconds;
    }

    public function lte($duration)
    {
        return $this->_seconds <= self::factory($duration)->_seconds;
    }


    // Microseconds
    public function setMicroseconds($us)
    {
        $this->_seconds = $us * self::MULTIPLIERS[self::MICROSECONDS];
        return $this;
    }

    public function getMicroseconds()
    {
        return $this->_seconds / self::MULTIPLIERS[self::MICROSECONDS];
    }

    public function addMicroseconds($us)
    {
        $this->_seconds += $us * self::MULTIPLIERS[self::MICROSECONDS];
        return $this;
    }

    public function subtractMicroseconds($us)
    {
        $this->_seconds -= $us * self::MULTIPLIERS[self::MICROSECONDS];
        return $this;
    }


    // Milliseconds
    public function setMilliseconds($ms)
    {
        $this->_seconds = $ms * self::MULTIPLIERS[self::MILLISECONDS];
        return $this;
    }

    public function getMilliseconds()
    {
        return $this->_seconds / self::MULTIPLIERS[self::MILLISECONDS];
    }

    public function addMilliseconds($ms)
    {
        $this->_seconds += $ms * self::MULTIPLIERS[self::MILLISECONDS];
        return $this;
    }

    public function subtractMilliseconds($ms)
    {
        $this->_seconds -= $ms * self::MULTIPLIERS[self::MILLISECONDS];
        return $this;
    }


    // Seconds
    public function setSeconds($seconds)
    {
        $this->_seconds = $seconds;
        return $this;
    }

    public function getSeconds()
    {
        return $this->_seconds;
    }

    public function addSeconds($seconds)
    {
        $this->_seconds += $seconds;
        return $this;
    }

    public function subtractSeconds($seconds)
    {
        $this->_seconds -= $seconds;
        return $this;
    }


    // Minutes
    public function setMinutes($minutes)
    {
        $this->_seconds = $minutes * self::MULTIPLIERS[self::MINUTES];
        return $this;
    }

    public function getMinutes()
    {
        return $this->_seconds / self::MULTIPLIERS[self::MINUTES];
    }

    public function addMinutes($minutes)
    {
        $this->_seconds += $minutes * self::MULTIPLIERS[self::MINUTES];
        return $this;
    }

    public function subtractMinutes($minutes)
    {
        $this->_seconds -= $minutes * self::MULTIPLIERS[self::MINUTES];
        return $this;
    }


    // Hours
    public function setHours($hours)
    {
        $this->_seconds = $hours * self::MULTIPLIERS[self::HOURS];
        return $this;
    }

    public function getHours()
    {
        return $this->_seconds / self::MULTIPLIERS[self::HOURS];
    }

    public function addHours($hours)
    {
        $this->_seconds += $hours * self::MULTIPLIERS[self::HOURS];
        return $this;
    }

    public function subtractHours($hours)
    {
        $this->_seconds -= $hours * self::MULTIPLIERS[self::HOURS];
        return $this;
    }


    // Days
    public function setDays($days)
    {
        $this->_seconds = $days * self::MULTIPLIERS[self::DAYS];
        return $this;
    }

    public function getDays()
    {
        return $this->_seconds / self::MULTIPLIERS[self::DAYS];
    }

    public function addDays($days)
    {
        $this->_seconds += $days * self::MULTIPLIERS[self::DAYS];
        return $this;
    }

    public function subtractDays($days)
    {
        $this->_seconds -= $days * self::MULTIPLIERS[self::DAYS];
        return $this;
    }


    // Weeks
    public function setWeeks($weeks)
    {
        $this->_seconds = $weeks * self::MULTIPLIERS[self::WEEKS];
        return $this;
    }

    public function getWeeks()
    {
        return $this->_seconds / self::MULTIPLIERS[self::WEEKS];
    }

    public function addWeeks($weeks)
    {
        $this->_seconds += $weeks * self::MULTIPLIERS[self::WEEKS];
        return $this;
    }

    public function subtractWeeks($weeks)
    {
        $this->_seconds -= $weeks * self::MULTIPLIERS[self::WEEKS];
        return $this;
    }


    // Months
    public function setMonths($months)
    {
        $this->_seconds = $months * self::MULTIPLIERS[self::MONTHS];
        return $this;
    }

    public function getMonths()
    {
        return $this->_seconds / self::MULTIPLIERS[self::MONTHS];
    }

    public function addMonths($months)
    {
        $this->_seconds += $months * self::MULTIPLIERS[self::MONTHS];
        return $this;
    }

    public function subtractMonths($months)
    {
        $this->_seconds -= $months * self::MULTIPLIERS[self::MONTHS];
        return $this;
    }


    // Years
    public function setYears($years)
    {
        $this->_seconds = $years * self::MULTIPLIERS[self::YEARS];
        return $this;
    }

    public function getYears()
    {
        return $this->_seconds / self::MULTIPLIERS[self::YEARS];
    }

    public function addYears($years)
    {
        $this->_seconds += $years * self::MULTIPLIERS[self::YEARS];
        return $this;
    }

    public function subtractYears($years)
    {
        $this->_seconds -= $years * self::MULTIPLIERS[self::YEARS];
        return $this;
    }


    // Locale
    public function setLocale($locale)
    {
        if ($locale === null) {
            $this->_locale = null;
        } else {
            $this->_locale = core\i18n\Locale::factory($locale);
        }

        return $this;
    }

    public function getLocale()
    {
        return $this->_locale;
    }

    // Format
    public static function normalizeUnitId($id)
    {
        if (is_string($id)) {
            switch (strtolower($id)) {
                case 'us':
                case 'microsecond':
                case 'microseconds':
                    $id = self::MICROSECONDS;
                    break;

                case 'ms':
                case 'millisecond':
                case 'milliseconds':
                    $id = self::MILLISECONDS;
                    break;

                case 'second':
                case 'seconds':
                    $id = self::SECONDS;
                    break;

                case 'minute':
                case 'minutes':
                    $id = self::MINUTES;
                    break;

                case 'hour':
                case 'hours':
                    $id = self::HOURS;
                    break;

                case 'day':
                case 'days':
                    $id = self::DAYS;
                    break;

                case 'week':
                case 'weeks':
                    $id = self::WEEKS;
                    break;

                case 'month':
                case 'months':
                    $id = self::MONTHS;
                    break;

                case 'year':
                case 'years':
                    $id = self::YEARS;
                    break;
            }
        }

        $id = (int)$id;

        switch ($id) {
            case self::MICROSECONDS:
            case self::MILLISECONDS:
            case self::SECONDS:
            case self::MINUTES:
            case self::HOURS:
            case self::DAYS:
            case self::WEEKS:
            case self::MONTHS:
            case self::YEARS:
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Invalid duration unit: ' . $id
                );
        }

        return $id;
    }

    public static function getUnitString($unit, $plural = true, $locale = null)
    {
        $unit = self::normalizeUnitId($unit);
        $i18n = core\i18n\Manager::getInstance();

        switch ($unit) {
            case self::MICROSECONDS:
                return $i18n->_([0 => 'microsecond', 1 => 'microseconds'], null, (int)$plural);

            case self::MILLISECONDS:
                return $i18n->_([0 => 'millisecond', 1 => 'milliseconds'], null, (int)$plural);

            case self::SECONDS:
                return $i18n->_([0 => 'second', 1 => 'seconds'], null, (int)$plural);

            case self::MINUTES:
                return $i18n->_([0 => 'minute', 1 => 'minutes'], null, (int)$plural);

            case self::HOURS:
                return $i18n->_([0 => 'hour', 1 => 'hours'], null, (int)$plural);

            case self::DAYS:
                return $i18n->_([0 => 'day', 1 => 'days'], null, (int)$plural);

            case self::WEEKS:
                return $i18n->_([0 => 'week', 1 => 'weeks'], null, (int)$plural);

            case self::MONTHS:
                return $i18n->_([0 => 'month', 1 => 'months'], null, (int)$plural);

            case self::YEARS:
                return $i18n->_([0 => 'year', 1 => 'years'], null, (int)$plural);
        }
    }

    public function toString($maxUnits = 1, $shortUnits = false, $maxUnit = self::YEARS, $roundLastUnit = true): string
    {
        return implode(', ', $this->_buildStringComponents($maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $this->_locale));
    }

    public function getUserString()
    {
        if ((int)$this->_seconds != $this->_seconds && abs($this->_seconds) <= self::MULTIPLIERS[self::WEEKS]) {
            return $this->getTimeFormatString();
        }

        return implode(' ', $this->_buildStringComponents(self::YEARS, false, self::YEARS, true, null));
    }

    public function getTimeFormatString()
    {
        $components = $this->_buildStringComponents(3, null, self::HOURS, null, null);

        if (count($components) == 1) {
            array_unshift($components, '00');
        }

        return implode(':', $components);
    }

    protected function _buildStringComponents($maxUnits = 1, $shortUnits = false, $maxUnit = self::YEARS, $roundLastUnit = true, $locale = null)
    {
        $i18n = core\i18n\Manager::getInstance();
        $seconds = $this->_seconds;

        $isNegative = false;

        if ($seconds < 0) {
            $seconds *= -1;
            $isNegative = true;
        }

        $minUnit = null;

        if (is_array($maxUnit)) {
            $temp = $maxUnit;
            $maxUnit = array_pop($temp);

            if (!$minUnit = array_shift($temp)) {
                $minUnit = $maxUnit;
            }
        }

        $maxUnit = self::normalizeUnitId($maxUnit);

        if ($minUnit !== null) {
            $minUnit = self::normalizeUnitId($minUnit);
        }

        if ($maxUnit == self::MICROSECONDS) {
            return [$this->_addUnitString($i18n, round($this->_seconds * 1000000), self::MICROSECONDS, $shortUnits)];
        } elseif ($maxUnit == self::MILLISECONDS || (($seconds != 0 && $seconds < 1) || ($seconds < 5 && (int)$seconds != $seconds))) {
            return [$this->_addUnitString($i18n, round($this->_seconds * 1000), self::MILLISECONDS, $shortUnits)];
        }

        $output = $this->_createOutputArray($seconds, $maxUnits, $minUnit, $maxUnit, $shortUnits === null);

        foreach ($output as $unit => $value) {
            if ($isNegative) {
                $value *= -1;
            }

            $round = false;

            if ($roundLastUnit === false) {
                $round = 1;

                if ($unit == self::SECONDS && $maxUnit == self::SECONDS) {
                    $round = 3;
                }
            } elseif ($roundLastUnit === true) {
                $round = 0;
            } elseif ($roundLastUnit !== null) {
                $round = abs((int)$roundLastUnit);
            }

            if ($round !== false) {
                $value = round($value, $round);
            }

            if ($shortUnits === null) {
                $parts = explode('.', $value, 2);
                $parts[0] = str_pad($parts[0], 2, '0', \STR_PAD_LEFT);
                $value = implode('.', $parts);
            } else {
                $value = $this->_addUnitString($i18n, $value, $unit, $shortUnits);
            }

            $output[$unit] = $value;
        }

        return $output;
    }

    public function __toString(): string
    {
        try {
            return (string)$this->toString();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function toUnit($unit)
    {
        $unit = self::normalizeUnitId($unit);

        switch ($unit) {
            case self::MICROSECONDS:
                return $this->getMicroseconds();

            case self::MILLISECONDS:
                return $this->getMilliseconds();

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

    private function _createOutputArray($seconds, $maxUnits, $minUnit, $maxUnit, $all = false)
    {
        if ($maxUnits <= 0) {
            $maxUnits = 1;
        }

        $output = [];
        $units = 0;
        $fraction = $seconds - (int)$seconds;

        for ($i = $maxUnit; $i > 0; $i--) {
            if ($i == 1) {
                $output[1] = $seconds + $fraction;
                break;
            }

            $multiplier = self::MULTIPLIERS[$i];

            if ($seconds < $multiplier) {
                continue;
            }

            $output[$i] = floor($seconds / $multiplier);
            $seconds %= $multiplier;
            $units++;

            if ($units >= $maxUnits || (!$all && !$seconds)) {
                if ($seconds) {
                    $output[$i] += $seconds / $multiplier;
                }

                break;
            }
        }

        for ($i = 1; $i < $minUnit; $i++) {
            if (isset($output[$i])) {
                if (!isset($output[$i + 1])) {
                    $output[$i + 1] = 0;
                }

                $output[$i + 1] += $output[$i] / self::MULTIPLIERS[$i];
                unset($output[$i]);
            }
        }

        return $output;
    }

    protected function _addUnitString(core\i18n\IManager $i18n, $number, $unit, $shortUnits = false)
    {
        switch ($unit) {
            case self::MICROSECONDS:
                return $i18n->_(
                    '%n% μs', // @ignore-non-ascii
                    ['%n%' => $number]
                );

            case self::MILLISECONDS:
                return $i18n->_(
                    '%n% ms',
                    ['%n%' => $number]
                );

            case self::SECONDS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% sc',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% second',
                            '*' => '%n% seconds'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::MINUTES:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% mn',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% minute',
                            '*' => '%n% minutes'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::HOURS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% hr',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% hour',
                            '*' => '%n% hours'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::DAYS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% dy',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% day',
                            '*' => '%n% days'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::WEEKS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% wk',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% week',
                            '*' => '%n% weeks'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::MONTHS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% mo',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% month',
                            '*' => '%n% months'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            case self::YEARS:
                if ($shortUnits) {
                    return $i18n->_(
                        '%n% yr',
                        ['%n%' => $number]
                    );
                } else {
                    return $i18n->_(
                        [
                            'n = 1 || n = -1' => '%n% year',
                            '*' => '%n% years'
                        ],
                        ['%n%' => $number],
                        $number
                    );
                }

                // no break
            default:
                return $number;
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->toString(7, true);
    }
}
