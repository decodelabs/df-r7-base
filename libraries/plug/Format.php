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

class Format implements core\ISharedHelper
{
    use core\TSharedHelper;

    // Numbers
    public function number($number, $round=null, $format=null, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($round !== null) {
            $number = round($number, $round);
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->format($number, $format);
    }

    public function percent($number, $total=100, int $maxDigits=0, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($total <= 0) {
            $number = 0;
        } else {
            $number = $number / $total;
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatRatioPercent($number, $maxDigits);
    }

    public function currency($number, $code=null, ?bool $rounded=null, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($number instanceof mint\ICurrency) {
            $code = $number->getCode();
            $number = $number->getAmount();
        }

        if ($code === null) {
            $code = 'USD';
        }

        $module = core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale);

        if ($rounded === true || ($rounded === null && (int)$number == $number)) {
            return $module->formatCurrencyRounded($number, $code);
        } else {
            return $module->formatCurrency($number, $code);
        }
    }

    public function scientific($number, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatScientific($number);
    }

    public function spellout($number, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatSpellout($number);
    }

    public function ordinal($number, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatOrdinal($number);
    }

    public function duration($duration, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null)
    {
        if ($duration === null) {
            return null;
        }

        $duration = core\time\Duration::factory($duration);

        if ($duration->isEmpty()) {
            return '0';
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($locale !== null) {
            $duration->setLocale($locale);
        }

        return $duration->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function genericDuration($number, $locale=null)
    {
        if ($number === null) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($number instanceof core\time\IDuration) {
            $number = $number->getSeconds();
        }

        $output = core\i18n\Manager::getInstance()
            ->getModule('numbers', $locale)
            ->formatDuration($number);

        if (preg_match('/([0-9]+) sec./', $output, $matches)) {
            $output = '0:'.$matches[1];
        }

        return $output;
    }

    public function fileSize($bytes)
    {
        if ($bytes === null) {
            return null;
        }

        $bytes = (int)$bytes;
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function binHex($binary)
    {
        if ($binary === null) {
            return null;
        }

        return bin2hex($binary);
    }



    // List
    public function commaList(?iterable $list, callable $renderer=null, int $limit=null, string $delimiter=', ', string $finalDelimiter=null): ?string
    {
        $renderer = $renderer ?? function ($value) {
            return $value;
        };

        if (!$list) {
            return null;
        }

        $output = [];
        $first = true;
        $i = $more = 0;

        if ($list instanceof \Countable || is_array($list)) {
            $total = count($list);
        } else {
            $total = null;
        }

        if ($finalDelimiter === null) {
            $finalDelimiter = $delimiter;
        }


        foreach ($list as $key => $item) {
            if ($item === null) {
                continue;
            }

            $i++;
            $cell = $renderer($item, $key, $i);

            if ($limit !== null && $i > $limit) {
                $more++;
                continue;
            }

            if (!$first) {
                if ($i == $total) {
                    $output[] = $finalDelimiter;
                } else {
                    $output[] = $delimiter;
                }
            }

            $first = false;
            $output[] = $cell;
        }

        if ($more) {
            if (!$first) {
                $output[] = $delimiter;
            }

            $output[] = $this->context->_('...and %c% more', ['%c%' => $more]);
        }

        return implode('', $output);
    }


    // Date
    public function date($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true)
    {
        if (!$date = $this->_prepareDate($date, $timezone, false)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeDateFormat($size, $locale);
    }

    public function dateTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true)
    {
        if (!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeFormat($size, $locale);
    }

    public function customDate($date, $format, $timezone=true)
    {
        if (!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        return $date->format($format);
    }

    public function time($date, $format=null, $timezone=true)
    {
        if (!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if ($format === null) {
            $format = 'g:ia';
        }

        return $date->format($format);
    }

    public function localeTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true)
    {
        if (!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        return $date->localeTimeFormat($size, $locale);
    }


    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null)
    {
        if (!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($locale === null) {
            $locale = true;
        }

        return $date->timeSince()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null)
    {
        if (!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($locale === null) {
            $locale = true;
        }

        return $date->timeUntil()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null)
    {
        if (!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->context->getLocale();
        }

        if ($locale === null) {
            $locale = true;
        }

        $ts = $date->toTimestamp();
        $now = core\time\Date::factory('now')->toTimestamp();
        $diff = $now - $ts;

        if ($diff > 0) {
            return $this->context->_(
                '%t% ago',
                ['%t%' => $this->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } elseif ($diff < 0) {
            return $this->context->_(
                'in %t%',
                ['%t%' => $this->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else {
            return $this->context->_('just now');
        }
    }

    protected function _prepareDate($date, $timezone=true, bool $includeTime=true)
    {
        if ($date instanceof core\time\ITimeOfDay) {
            return new core\time\Date($date);
        }

        if ($timezone === false) {
            $timezone = null;
            $includeTime = false;
        }

        if (!$date = core\time\Date::normalize($date, null, $includeTime)) {
            return null;
        }

        if ($timezone !== null) {
            $date = clone $date;

            if ($date->hasTime()) {
                if ($timezone === true) {
                    $date->toUserTimeZone();
                } else {
                    $date->toTimezone($timezone);
                }
            }
        }

        return $date;
    }


    // Strings
    public function name($name)
    {
        return flex\Text::formatName($name);
    }

    public function initials($name)
    {
        return flex\Text::formatInitials($name);
    }

    public function consonants($text)
    {
        return flex\Text::formatConsonants($text);
    }

    public function id($id)
    {
        return flex\Text::formatId($id);
    }

    public function constant($const)
    {
        return flex\Text::formatConstant($const);
    }

    public function nodeSlug($node)
    {
        return flex\Text::formatNodeSlug($node);
    }

    public function slug($slug)
    {
        return flex\Text::formatSlug($slug);
    }

    public function pathSlug($slug)
    {
        return flex\Text::formatPathSlug($slug);
    }

    public function fileName($fileName)
    {
        return flex\Text::formatFileName($fileName);
    }

    public function numericToAlpha($number)
    {
        return flex\Text::numericToAlpha($number);
    }

    public function alphaToNumeric($alpha)
    {
        return flex\Text::alphaToNumeric($alpha);
    }

    public function shorten($string, $length=20, $right=false)
    {
        return flex\Text::shorten($string, $length, $right);
    }

    public function stringToBoolean($string, $default=true)
    {
        return flex\Text::stringToBoolean($string, $default);
    }


    public function firstName($fullName)
    {
        $parts = explode(' ', $fullName);
        $output = (string)array_shift($parts);

        if (in_array(strtolower($output), ['mr', 'ms', 'mrs', 'miss', 'dr'])) {
            if (isset($parts[1])) {
                $output = array_shift($parts);
            } else {
                $output = $fullName;
            }
        }

        if (strlen($output) < 3) {
            $output = $fullName;
        }

        return $output;
    }

    public function initialsAndSurname($name): string
    {
        $parts = explode(' ', $name);
        $surname = array_pop($parts);

        if (in_array(strtolower($parts[0] ?? ''), ['mr', 'ms', 'mrs', 'miss', 'dr'])) {
            array_shift($parts);
        }

        return flex\Text::formatInitials(implode(' ', $parts), false).' '.$surname;
    }

    public function initialMiddleNames($name): string
    {
        $parts = explode(' ', $name);
        $surname = array_pop($parts);

        if (in_array(strtolower($parts[0] ?? ''), ['mr', 'ms', 'mrs', 'miss', 'dr'])) {
            array_shift($parts);
        }

        $output = (string)array_shift($parts);

        if (!empty($output)) {
            $output .= ' ';
        }

        if (!empty($parts)) {
            $output .= flex\Text::formatInitials(implode(' ', $parts), false).' ';
        }

        $output .= $surname;
        return $output;
    }

    public function email($address, $name=null, $visual=false)
    {
        $output = (string)flow\mail\Address::factory($address, $name);

        if ($visual) {
            $output = str_ireplace(['%2b', '"'], ['+', ''], $output);
        }

        return $output;
    }


    public function counterNote($counter)
    {
        if ($counter) {
            return '('.$this->number($counter).')';
        }
    }





    public function shortenGuid(string $id): string
    {
        return flex\Guid::shorten($id);
    }

    public function unshortenGuid(string $id): string
    {
        return flex\Guid::unshorten($id);
    }
}
