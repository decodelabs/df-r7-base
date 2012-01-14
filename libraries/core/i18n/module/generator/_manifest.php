<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module\generator;

use df\core;

// Exceptions
interface IException extends core\i18n\module\IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface IModule extends core\i18n\module\IModule {
    public function _convertCldr(core\i18n\ILocale $locale, \SimpleXMLElement $doc);
}

interface IGenerator {
    public function setCldrPath($path);
    public function getCldrPath();
    public function setSavePath($path);
    public function getSavePath();
    
    public function setModules($modules);
    public function addModules($modules);
    public function addModule($module);
    public function removeModule($module);
    public function getModules();
    public function clearModules();
    
    public function generate();
}
