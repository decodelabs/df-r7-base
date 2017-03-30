<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df\core;

class Config extends core\Config {

    const ID = 'I18n';

    public function getDefaultValues(): array {
        return [
            'locale' => [
                'default' => 'en_GB',
                'allow' => [],
                'deny' => [],
                'detectClient' => false
            ],
            'translation' => [
                'enabled' => false
            ]
        ];
    }


// Default locale
    public function setDefaultLocale($locale) {
        $locale = Locale::factory($locale);

        if(!$this->isLocaleAllowed($locale)) {
            throw new RuntimeException('Default locale '.$locale.' is not allowed');
        }

        $this->values->locale->default = (string)$locale;
        return $this;
    }

    public function getDefaultLocale() {
        return $this->values->locale->get('default', 'en_GB');
    }

// Locale filter
    public function setAllowedLocales(array $locales) {
        $allow = [];

        foreach($locales as $locale) {
            $allow[] = (string)Locale::factory($locale);
        }

        $this->values->locale->allow = $allow;
        return $this;
    }

    public function getAllowedLocales() {
        return $this->values->locale->allow->toArray();
    }

    public function setDeniedLocales(array $locales) {
        $deny = [];

        foreach($locales as $locale) {
            $deny[] = (string)Locale::factory($locale);
        }

        $this->values->locale->deny = $deny;
        return $this;
    }

    public function getDeniedLocales() {
        return $this->values->locale->deny->toArray();
    }

    public function isLocaleAllowed($locale) {
        $locale = Locale::factory($locale);

        $test = array_unique([
            (string)$locale,
            $locale->getLanguage().'_'.$locale->getRegion(),
            $locale->getLanguage()
        ]);

        if(!$this->values->locale->allow->isEmpty()) {
            $allow = $this->values->locale->allow->toArray();

            foreach($test as $testLocale) {
                if(in_array($testLocale, $allow)) {
                    return true;
                }
            }

            return false;
        }

        if(!$this->values->locale->deny->isEmpty()) {
            $allow = $this->values->locale->deny->toArray();

            foreach($test as $testLocale) {
                if(in_array($testLocale, $deny)) {
                    return false;
                }
            }

            return true;
        }

        return true;
    }

// Detect
    public function shouldDetectClientLocale(bool $flag=null) {
        if($flag !== null) {
            $this->values->locale->detectClient = $flag;
            return $this;
        }

        return (bool)$this->values->locale['detectClient'];
    }

// Translation
    public function isTranslationEnabled(bool $flag=null) {
        if($flag !== null) {
            $this->values->translation->enabled = $flag;
            return $this;
        }

        return (bool)$this->values->translation['enabled'];
    }
}