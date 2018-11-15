<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

// Exceptions
interface IException extends core\i18n\IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}


// Interfaces
interface IModule
{
    public function getModuleName();
}


interface IListModule extends IModule
{
    public function getName($id);
    public function getList(array $ids=null);
    public function getCodeList();
    public function isValidId($id);
}

interface ICountriesModule extends IListModule
{
}
interface ITerritoriesModule extends IListModule
{
}
interface IScriptsModule extends IListModule
{
}

interface ILanguagesModule extends IListModule
{
    public function getExtendedList();
}

interface IDatesModules extends IModule
{
    public function getCalendarList();
    public function getDefaultCalendar();
    public function getDayName($day=null, $calendar=null);
    public function getDayList($calendar=null);
    public function getAbbreviatedDayList($calendar=null);
    public function getMonthName($month=null, $calendar=null);
    public function getMonthList($calendar=null);
    public function getAbbreviatedMonthList($calendar=null);
}

interface ITimezonesModule extends IListModule
{
    public function forCountry($country=null);
    public function suggestForCountry($country=null);
    public function forContinent($continent);
    public function getContinentList();
    public function getCountryList();
    public function getOffset($timezone);
}

interface INumbersModule extends IModule
{
    public function format($number, $format=null);
    public function parse($number, $type=self::DOUBLE, &$pos=0, $format=null);
    public function formatPercent($number, int $maxDigits=3);
    public function formatRatioPercent($number, int $maxDigits=3);
    public function parsePercent($number);
    public function parseRatioPercent($number);
    public function formatCurrency($amount, $code);
    public function parseCurrency($amount, $code);
    public function getCurrencyName($code);
    public function getCurrencySymbol($code, $amount=1);
    public function getCurrencyList();
    public function isValidCurrency($code);
    public function formatScientific($number);
    public function parseScientific($number);
    public function formatSpellout($number);
    public function parseSpellout($number);
    public function formatOrdinal($number);
    public function parseOrdinal($number);
    public function formatDuration($number);
    public function parseDuration($number);
    public function formatFileSize($bytes, $precision=2, $longNames=false);
}
