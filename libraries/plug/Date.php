<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\mint;

class Date implements core\ISharedHelper {
    
    use core\TSharedHelper;

    public function __invoke($date, $timezone=null) {
        return core\time\Date::factory($date, $timezone);
    }

    public function now() {
        return new core\time\Date();
    }

    public function fromCompressedString($string, $timezone=true) {
        return core\time\Date::fromCompressedString($string, $timezone);
    }

    public function fromLocaleString($string, $timezone=true, $size=core\time\Date::SHORT, $locale=null) {
        return core\time\Date::fromLocaleString($string, $timezone, $size, $locale);
    }

    public function fromFormatString($date, $format, $timezone=true, $locale=null) {
        return core\time\Date::fromFormatString($date, $format, $timezone, $locale);
    }


// Relative
    public function eq($date) {
        return $this->now()->eq($date);
    }

    public function is($date) {
        return $this->now()->is($date);
    }

    public function isBetween($start, $end) {
        return $this->now()->isBetween($start, $end);
    }

    public function gt($date) {
        return $this->now()->gt($date);
    }

    public function gte($date) {
        return $this->now()->gte($date);
    }

    public function lt($date) {
        return $this->now()->lt($date);
    }

    public function lte($date) {
        return $this->now()->lte($date);
    }
}