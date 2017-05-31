<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\mint;
use df\flow;
use df\flex;

class Format implements core\ISharedHelper {

    use core\TSharedHelper;

// Numbers
    public function number($number, $round=null, $format=null, $locale=null) {
        if($number === null) {
            return null;
        }

        if($round !== null) {
            $number = round($number, $round);
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->format($number, $format);
    }

    public function percent($number, $total=100, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($total <= 0) {
            $number = 0;
        } else {
            $number = $number / $total;
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatRatioPercent($number);
    }

    public function currency($number, $code=null, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($number instanceof mint\ICurrency) {
            $code = $number->getCode();
            $number = $number->getAmount();
        }

        if($code === null) {
            $code = 'USD';
        }

        $module = core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale);

        if((int)$number == $number) {
            return $module->formatCurrencyRounded($number, $code);
        } else {
            return $module->formatCurrency($number, $code);
        }
    }

    public function currencyRounded($number, $code=null, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($number instanceof mint\ICurrency) {
            $code = $number->getCode();
            $number = $number->getAmount();
        }

        if($code === null) {
            $code = 'USD';
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatCurrencyRounded($number, $code);
    }

    public function scientific($number, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatScientific($number);
    }

    public function spellout($number, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatSpellout($number);
    }

    public function ordinal($number, $locale=null) {
        if($number === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatOrdinal($number);
    }

    public function duration($duration, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if($duration === null) {
            return null;
        }

        $duration = core\time\Duration::factory($duration);

        if($duration->isEmpty()) {
            return '0';
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale !== null) {
            $duration->setLocale($locale);
        }

        return $duration->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function genericDuration($number, $locale=null) {
        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatDuration($number);
    }

    public function fileSize($bytes, $precision=2, $longNames=false, $locale=null) {
        if($bytes === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatFileSize($bytes, $precision, $longNames);
    }

    public function binHex($binary) {
        if($binary === null) {
            return null;
        }

        return bin2hex($binary);
    }


// Date
    public function date($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, false)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeDateFormat($size, $locale);
    }

    public function dateTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeFormat($size, $locale);
    }

    public function customDate($date, $format, $timezone=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if($timezone !== null) {
            $date = clone $date;

            if($timezone === true) {
                $date->toUserTimeZone();
            } else {
                $date->setTimezone($timezone);
            }
        }

        return $date->format($format);
    }

    public function time($date, $format=null, $timezone=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if($userTime) {
            $date = clone $date;
            $date->toUserTimeZone();
        }

        if($format === null) {
            $format = 'g:ia';
        }

        return $date->format($format);
    }

    public function localeTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeTimeFormat($size, $locale);
    }


    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }

        return $date->timeSince()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }

        return $date->timeUntil()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }

        $ts = $date->toTimestamp();
        $now = core\time\Date::factory('now')->toTimestamp();
        $diff = $now - $ts;

        if($diff > 0) {
            return $this->context->_(
                '%t% ago',
                ['%t%' => $this->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else if($diff < 0) {
            return $this->context->_(
                'in %t%',
                ['%t%' => $this->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else {
            return $this->context->_('just now');
        }
    }


    protected function _prepareDate($date, $timezone=true, bool $includeTime=true) {
        if(!$date = core\time\Date::normalize($date, null, $includeTime)) {
            return null;
        }

        if($timezone !== null) {
            $date = clone $date;

            if($date->hasTime()) {
                if($timezone === true) {
                    $date->toUserTimeZone();
                } else {
                    $date->toTimezone($timezone);
                }
            }
        }

        return $date;
    }


// Strings
    public function name($name) {
        return flex\Text::formatName($name);
    }

    public function initials($name) {
        return flex\Text::formatInitials($name);
    }

    public function id($id) {
        return flex\Text::formatId($id);
    }

    public function constant($const) {
        return flex\Text::formatConstant($const);
    }

    public function nodeSlug($node) {
        return flex\Text::formatNodeSlug($node);
    }

    public function slug($slug) {
        return flex\Text::formatSlug($slug);
    }

    public function pathSlug($slug) {
        return flex\Text::formatPathSlug($slug);
    }

    public function fileName($fileName) {
        return flex\Text::formatFileName($fileName);
    }

    public function numericToAlpha($number) {
        return flex\Text::numericToAlpha($number);
    }

    public function alphaToNumeric($alpha) {
        return flex\Text::alphaToNumeric($alpha);
    }

    public function shorten($string, $length=20, $right=false) {
        return flex\Text::shorten($string, $length, $right);
    }

    public function stringToBoolean($string, $default=true) {
        return flex\Text::stringToBoolean($string, $default);
    }


    public function firstName($fullName) {
        $parts = explode(' ', $fullName);
        $output = array_shift($parts);

        if(in_array(strtolower($output), ['mr', 'ms', 'mrs', 'miss', 'dr'])) {
            if(isset($parts[1])) {
                $output = array_shift($parts);
            } else {
                $output = $fullName;
            }
        }

        if(strlen($output) < 3) {
            $output = $fullName;
        }

        return $output;
    }

    public function email($address, $name=null, $visual=false) {
        $output = (string)flow\mail\Address::factory($address, $name);

        if($visual) {
            $output = str_ireplace(['%2b', '"'], ['+', ''], $output);
        }

        return $output;
    }


    public function counterNote($counter) {
        if($counter) {
            return '('.$this->number($counter).')';
        }
    }
}
