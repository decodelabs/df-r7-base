<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df;
use df\core;

class Manager implements IManager {

    use core\TManager;
    use core\TTranslator;

    const REGISTRY_PREFIX = 'manager://i18n';

    protected $_locale;
    protected $_modules = [];
    protected $_translator;


// Locale
    public function setLocale($locale) {
        if($locale === null) {
            $locale = $this->getDefaultLocale();
        }

        $this->_locale = Locale::factory($locale);
        $string = $this->_locale->__toString();

        setlocale(LC_ALL, $string);
        \Locale::setDefault($string);

        return $this;
    }

    public function getLocale() {
        if($this->_locale === null) {
            $this->_locale = $this->getDefaultLocale();
        }

        return $this->_locale;
    }

    public function getDefaultLocale() {
        $config = Config::getInstance();
        $default = null;

        if($config->shouldDetectClientLocale()
        && df\Launchpad::$runner instanceof core\app\runner\Http) {
            $request = df\Launchpad::$runner->getHttpRequest();

            if(isset($request->headers['accept-language'])) {
                $default = \Locale::acceptFromHttp($request->headers['accept-language']);
            }
        }

        if(!$default) {
            $default = $config->getDefaultLocale();
        }

        if(!$default) {
            $default = \Locale::getDefault();
        }

        if(!$default) {
            $default = 'en_GB';
        }

        return Locale::factory($default);
    }


// Modules
    public function getModule($name, $locale=null) {
        if($locale === null) {
            $locale = $this->getLocale();
        } else {
            $locale = Locale::factory($locale);
        }

        $id = $name.':'.$locale;

        if(!isset($this->_modules[$id])) {
            $this->_modules[$id] = core\i18n\module\Base::factory($this, $name, $locale);
        }

        return $this->_modules[$id];
    }

    public function __get($member) {
        switch($member) {
            case 'locale':
                return $this->getLocale();

            default:
                return $this->getModule($member);
        }
    }


// Translate
    public function translate(array $args): string {
        if(!$this->_translator) {
            $this->_translator = Translator::factory('i18n');
        }

        return $this->_translator->translate($args);
    }
}
