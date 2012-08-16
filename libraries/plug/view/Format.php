<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;

class Format implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
// Numbers
    public function number($number, $format=null, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->format($number, $format);
    }
    
    public function percent($number, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatPercent($number);
    }
    
    public function currency($number, $code, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatCurrency($number, $code);
    }
    
    public function scientific($number, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatScientific($number);
    }
    
    public function spellout($number, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatSpellout($number);
    }
    
    public function ordinal($number, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatOrdinal($number);
    }
    
    public function duration($number, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatDuration($number);
    }

    public function fileSize($bytes, $precision=2, $longNames=false, $locale=null) {
        return core\i18n\Manager::getInstance($this->_view->getContext()->getApplication())->getModule('numbers', $locale)->formatFileSize($bytes, $precision, $longNames);
    }

    
// Date
    public function date($date, $size=core\time\Date::MEDIUM, $locale=true) {
        return core\time\Date::factory($date)
            ->localeDateFormat($size, $locale);
    }
    
    public function userDate($date, $size=core\time\Date::MEDIUM) {
        return core\time\Date::factory($date)
            ->userLocaleDateFormat($size);
    }
    
    public function dateTime($date, $size=core\time\Date::MEDIUM, $locale=true) {
        return core\time\Date::factory($date)
            ->localeFormat($size, $locale);
    }
    
    public function userDateTime($date, $size=core\time\Date::MEDIUM) {
        return core\time\Date::factory($date)
            ->userLocaleFormat($size);
    }
    
    public function time($date, $size=core\time\Date::MEDIUM, $locale=true) {
        return core\time\Date::factory($date)
            ->localeTimeFormat($size, $locale);
    }
    
    public function userTime($date, $size=core\time\Date::MEDIUM) {
        return core\time\Date::factory($date)
            ->userLocaleTimeFormat($size);
    }
    
    
    public function timeSince($date, $locale=true) {
        return core\time\Date::factory($date)
            ->timeSince()
            ->setLocale($locale)
            ->toString();
    }
    
    public function timeUntil($date, $locale=true) {
        return core\time\Date::factory($date)
            ->timeUntil()
            ->setLocale($locale)
            ->toString();
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
    
    public function filename($filename) {
        return core\string\Manipulator::formatFilename($filename);
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
}
