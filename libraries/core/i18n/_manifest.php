<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n;

use df;
use df\core;

interface ILocale extends core\IStringProvider
{
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


interface IManager extends core\IManager, core\ITranslator
{
    public function getModule($name);
    public function setLocale($locale);
    public function getLocale();
}

interface ITranslator extends core\ITranslator
{
}
