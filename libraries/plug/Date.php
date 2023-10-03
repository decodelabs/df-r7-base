<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use df\core;

class Date implements core\ISharedHelper
{
    use core\TSharedHelper;

    public function __invoke($date, $timezone = null)
    {
        return core\time\Date::factory($date, $timezone);
    }

    public function __call($method, array $args)
    {
        return $this->now()->{$method}(...$args);
    }

    public function now()
    {
        return new core\time\Date();
    }

    public function fromCompressedString($string, $timezone = true)
    {
        return core\time\Date::fromCompressedString($string, $timezone);
    }

    public function fromLocaleString($string, $timezone = true, $size = core\time\Date::SHORT, $locale = null)
    {
        return core\time\Date::fromLocaleString($string, $timezone, $size, $locale);
    }

    public function fromFormatString($date, $format, $timezone = true, $locale = null)
    {
        return core\time\Date::fromFormatString($date, $format, $timezone, $locale);
    }



    public function getMonthList(int $startMonth = null)
    {
        if ($startMonth === null) {
            $startMonth = 1;
        }

        $date = new core\time\Date($startMonth . '/1');
        $output = [];

        for ($i = 0; $i < 12; $i++) {
            $output[$date->format('n')] = $date->format('F');
            $date->modify('+1 month');
        }

        return $output;
    }

    public function getYearList(int $startYear = null, int $length = null)
    {
        if ($startYear === null) {
            $startYear = $this->now()->format('Y');
        }

        if ($length === null) {
            $length = 10;
        }

        $output = [];
        $year = $startYear;

        for ($i = 0; $i < $length; $i++) {
            $strYear = str_pad($year, 4, '0', \STR_PAD_LEFT);
            $output[$strYear] = $strYear;
            $year++;
        }

        return $output;
    }

    public function formatDuration($duration, $maxUnits = 1, $shortUnits = false, $maxUnit = core\time\Duration::YEARS, $roundLastUnit = true, $locale = null)
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

    public function formatGenericDuration($number, $locale = null)
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

        if (preg_match('/([0-9]+) sec./', (string)$output, $matches)) {
            $output = '0:' . $matches[1];
        }

        return $output;
    }
}
