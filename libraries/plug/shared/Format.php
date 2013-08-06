<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\aura;
use df\mint;

class Format implements core\ISharedHelper {
    
    use core\TSharedHelper;
    
// Numbers
    public function number($number, $format=null, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->format($number, $format);
    }
    
    public function percent($number, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatPercent($number);
    }
    
    public function calcPercent($divisor, $total, $locale=null) {
        if($total <= 0) {
            $number = 0;
        } else {
            $number = $divisor / $total;
        }

        return $this->percent($number, $locale);
    }

    public function currency($number, $code=null, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        if($number instanceof mint\ICurrency) {
            $code = $number->getCode();
            $number = $number->getAmount();
        }

        if($code === null) {
            $code = 'USD';
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatCurrency($number, $code);
    }
    
    public function scientific($number, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatScientific($number);
    }
    
    public function spellout($number, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatSpellout($number);
    }
    
    public function ordinal($number, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatOrdinal($number);
    }
    
    public function duration($duration, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if($duration === null) {
            return null;
        }

        $duration = core\time\Duration::factory($duration);

        if($duration->isEmpty()) {
            return null;
        }

        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        if($locale !== null) {
            $duration->setLocale($locale);
        }

        return $duration->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function genericDuration($number, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
            ->getModule('numbers', $locale)
            ->formatDuration($number);
    }

    public function fileSize($bytes, $precision=2, $longNames=false, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\i18n\Manager::getInstance($this->_context->application)
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
    public function date($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        return core\time\Date::factory($date)
            ->localeDateFormat($size, $locale);
    }
    
    public function userDate($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }
        
        return core\time\Date::factory($date)
            ->userLocaleDateFormat($size);
    }
    
    public function dateTime($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->_context->getLocale();
        }
        
        return core\time\Date::factory($date)
            ->localeFormat($size, $locale);
    }
    
    public function userDateTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }
        
        return core\time\Date::factory($date)
            ->userLocaleFormat($size);
    }

    public function customDate($date, $format) {
        if($date === null) {
            return null;
        }

        return core\time\Date::factory($date)
            ->format($format);
    }
    
    public function time($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        if($locale === null) {
            $locale = $this->_context->getLocale();
        }
        
        return core\time\Date::factory($date)
            ->localeTimeFormat($size, $locale);
    }
    
    public function userTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }
        
        return core\time\Date::factory($date)
            ->userLocaleTimeFormat($size);
    }
    
    
    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }
        
        return core\time\Date::factory($date)
            ->timeSince()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }
    
    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if($locale === null) {
            $locale = $this->_context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }
        
        return core\time\Date::factory($date)
            ->timeUntil()
            ->setLocale($locale)
            ->toString($maxUnits, $shortUnits, $maxUnit, $roundLastUnit);
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        $date = core\time\Date::factory($date);

        if($date->isPast()) {
            return $this->_context->_(
                '%t% ago',
                ['%t%' => $this->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else {
            return $this->_context->_(
                'in %t%',
                ['%t%' => $this->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        }
    }
    
    
// Strings
    public function name($name) {
        return core\string\Manipulator::formatName($name);
    }
    
    public function id($id) {
        return core\string\Manipulator::formatId($id);
    }
    
    public function slug($slug) {
        return core\string\Manipulator::formatSlug($slug);
    }
    
    public function pathSlug($slug) {
        return core\string\Manipulator::formatPathSlug($slug);
    }
    
    public function fileName($fileName) {
        return core\string\Manipulator::formatFileName($fileName);
    }
    
    public function numericToAlpha($number) {
        return core\string\Manipulator::numericToAlpha($number);
    }
    
    public function alphaToNumeric($alpha) {
        return core\string\Manipulator::alphaToNumeric($alpha);
    }

    public function shorten($string, $length=20) {
        return core\string\Manipulator::shorten($string, $length);
    }



    public function counterNote($counter) {
        if($counter) {
            return '('.$this->number($counter).')';
        }
    }
}
