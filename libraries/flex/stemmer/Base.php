<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\stemmer;

use df;
use df\core;
use df\flex;

abstract class Base implements flex\IStemmer {

    public static function factory($locale=null) {
        $locale = core\i18n\Locale::factory($locale);
        $language = ucfirst($locale->getLanguage());
        $class = 'df\\flex\\stemmer\\'.$language;

        if(!class_exists($class)) {
            throw new flex\RuntimeException(
                'No stemmer available for '.$locale
            );
        }

        return new $class();
    }
}