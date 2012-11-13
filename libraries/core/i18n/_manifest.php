<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ILocale extends core\IStringProvider {
    public function getDisplayName($localeFormat=null);
    public function getLanguage();
    public function getDisplayLanguage($localeFormat=null);
    public function getScript();
    public function getDisplayScript($localeFormat=null);
    public function getRegion();
    public function getDisplayRegion($localeFormat=null);
    public function getVariants();
    public function getDisplayVariants($localeFormat=null);
    public function getKeywords();
}


interface IManager extends core\IManager {
    public function getModule($name);
    public function setLocale($locale);
    public function getLocale();
}
