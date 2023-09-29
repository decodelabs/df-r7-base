<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n\module;

use DecodeLabs\Exceptional;

class Timezones extends Base implements ITimezonesModule
{
    public function getName($id)
    {
        return $id;
    }

    public function forCountry($country = null)
    {
        if ($country === null) {
            $country = $this->_manager->getLocale()->getCountry();
        }

        $country = strtoupper((string)$country);

        if (isset(self::COUNTRIES[$country])) {
            return self::COUNTRIES[$country];
        } else {
            return [];
        }
    }

    public function suggestForCountry($country = null)
    {
        $list = $this->forCountry($country);

        if (!empty($list)) {
            return array_shift($list);
        }

        return 'UTC';
    }

    public function forContinent($continent)
    {
        self::_createContinentList();
        $continent = ucfirst(strtolower((string)$continent));

        if (isset(self::$_continents[$continent])) {
            return self::$_continents[$continent];
        } else {
            return [];
        }
    }

    private static function _createContinentList()
    {
        if (!count(self::$_continents)) {
            foreach (self::COUNTRIES as $country) {
                foreach ($country as $tz) {
                    $a = explode('/', $tz, 2);
                    $cn = current($a);
                    self::$_continents[$cn][] = $tz;
                }

                sort(self::$_continents[$cn]);
            }

            ksort(self::$_continents);
        }
    }

    public function getList(array $ids = null)
    {
        $output = [];

        foreach ($this->getContinentList() as $key => $val) {
            $output = array_merge($output, $val);
        }

        if ($ids !== null) {
            $output = array_intersect_key($output, array_flip(array_values($ids)));
        }

        return $output;
    }

    public function getCodeList()
    {
        return array_keys($this->getList());
    }

    public function getContinentList()
    {
        self::_createContinentList();
        return self::$_continents;
    }

    public function getCountryList()
    {
        return self::COUNTRIES;
    }

    public function getOffset($timezone)
    {
        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }

        if (!$timezone instanceof \DateTimeZone) {
            throw Exceptional::InvalidArgument(
                'Invalid timezone specified!'
            );
        }

        $date = new \DateTime('now', $timezone);
        return $timezone->getOffset($date);
    }

    public function isValidId($id)
    {
        try {
            return (bool)\timezone_open($id);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static $_continents = [];
    public const COUNTRIES = [
        'AD' => ['Europe/Andorra'],
        'AE' => ['Asia/Dubai'],
        'AF' => ['Asia/Kabul'],
        'AG' => ['America/Antigua'],
        'AI' => ['America/Anguilla'],
        'AL' => ['Europe/Tirane'],
        'AM' => ['Asia/Yerevan'],
        'AN' => ['America/Curacao'],
        'AO' => ['Africa/Luanda'],
        'AQ' => [
            'Antarctica/McMurdo',
            'Antarctica/South_Pole',
            'Antarctica/Rothera',
            'Antarctica/Palmer',
            'Antarctica/Mawson',
            'Antarctica/Davis',
            'Antarctica/Casey',
            'Antarctica/Vostok',
            'Antarctica/DumontDUrville',
            'Antarctica/Syowa'
        ],
        'AR' => [
            'America/Buenos_Aires',
            'America/Cordoba',
            'America/Jujuy',
            'America/Tucuman',
            'America/Catamarca',
            'America/La_Rioja',
            'America/San_Juan',
            'America/Mendoza',
            'America/ComodRivadavia',
            'America/Rio_Gallegos',
            'America/Ushuaia'
        ],
        'AS' => ['Pacific/Pago_Pago'],
        'AT' => ['Europe/Vienna'],
        'AU' => [
            'Australia/Lord_Howe',
            'Australia/Hobart',
            'Australia/Melbourne',
            'Australia/Sydney',
            'Australia/Broken_Hill',
            'Australia/Brisbane',
            'Australia/Lindeman',
            'Australia/Adelaide',
            'Australia/Darwin',
            'Australia/Perth'
        ],
        'AW' => ['America/Aruba'],
        'AX' => ['Europe/Mariehamn'],
        'AZ' => ['Asia/Baku'],
        'BA' => ['Europe/Sarajevo'],
        'BB' => ['America/Barbados'],
        'BD' => ['Asia/Dhaka'],
        'BE' => ['Europe/Brussels'],
        'BF' => ['Africa/Ouagadougou'],
        'BG' => ['Europe/Sofia'],
        'BH' => ['Asia/Bahrain'],
        'BI' => ['Africa/Bujumbura'],
        'BJ' => ['Africa/Porto-Novo'],
        'BM' => ['Atlantic/Bermuda'],
        'BN' => ['Asia/Brunei'],
        'BO' => ['America/La_Paz'],
        'BR' => [
            'America/Noronha',
            'America/Belem',
            'America/Fortaleza',
            'America/Recife',
            'America/Araguaina',
            'America/Maceio',
            'America/Bahia',
            'America/Sao_Paulo',
            'America/Campo_Grande',
            'America/Cuiaba',
            'America/Porto_Velho',
            'America/Boa_Vista',
            'America/Manaus',
            'America/Eirunepe',
            'America/Rio_Branco'
        ],
        'BS' => ['America/Nassau'],
        'BT' => ['Asia/Thimphu'],
        'BW' => ['Africa/Gaborone'],
        'BY' => ['Europe/Minsk'],
        'BZ' => ['America/Belize'],
        'CA' => [
            'America/St_Johns',
            'America/Halifax',
            'America/Glace_Bay',
            'America/Goose_Bay',
            'America/Montreal',
            'America/Toronto',
            'America/Nipigon',
            'America/Thunder_Bay',
            'America/Pangnirtung',
            'America/Iqaluit',
            'America/Rankin_Inlet',
            'America/Winnipeg',
            'America/Rainy_River',
            'America/Cambridge_Bay',
            'America/Regina',
            'America/Swift_Current',
            'America/Edmonton',
            'America/Yellowknife',
            'America/Inuvik',
            'America/Dawson_Creek',
            'America/Vancouver',
            'America/Whitehorse',
            'America/Dawson'
        ],
        'CC' => ['Indian/Cocos'],
        'CD' => [
            'Africa/Kinshasa',
            'Africa/Lubumbashi'
        ],
        'CF' => ['Africa/Bangui'],
        'CG' => ['Africa/Brazzaville'],
        'CH' => ['Europe/Zurich'],
        'CI' => ['Africa/Abidjan'],
        'CK' => ['Pacific/Rarotonga'],
        'CL' => [
            'America/Santiago',
            'Pacific/Easter'
        ],
        'CM' => ['Africa/Douala'],
        'CN' => [
            'Asia/Shanghai',
            'Asia/Harbin',
            'Asia/Chongqing',
            'Asia/Urumqi',
            'Asia/Kashgar'
        ],
        'CO' => ['America/Bogota'],
        'CR' => ['America/Costa_Rica'],
        'CS' => ['Europe/Belgrade'],
        'CU' => ['America/Havana'],
        'CV' => ['Atlantic/Cape_Verde'],
        'CX' => ['Indian/Christmas'],
        'CY' => ['Asia/Nicosia'],
        'CZ' => ['Europe/Prague'],
        'DE' => ['Europe/Berlin'],
        'DJ' => ['Africa/Djibouti'],
        'DK' => ['Europe/Copenhagen'],
        'DM' => ['America/Dominica'],
        'DO' => ['America/Santo_Domingo'],
        'DZ' => ['Africa/Algiers'],
        'EC' => [
            'America/Guayaquil',
            'Pacific/Galapagos'
        ],
        'EE' => ['Europe/Tallinn'],
        'EG' => ['Africa/Cairo'],
        'EH' => ['Africa/El_Aaiun'],
        'ER' => ['Africa/Asmera'],
        'ES' => [
            'Europe/Madrid',
            'Africa/Ceuta',
            'Atlantic/Canary'
        ],
        'ET' => ['Africa/Addis_Ababa'],
        'FI' => ['Europe/Helsinki'],
        'FJ' => ['Pacific/Fiji'],
        'FK' => ['Atlantic/Stanley'],
        'FM' => [
            'Pacific/Yap',
            'Pacific/Truk',
            'Pacific/Ponape',
            'Pacific/Kosrae'
        ],
        'FO' => ['Atlantic/Faeroe'],
        'FR' => ['Europe/Paris'],
        'GA' => ['Africa/Libreville'],
        'GB' => [
            'Europe/London',
            'Europe/Belfast'
        ],
        'GD' => ['America/Grenada'],
        'GE' => ['Asia/Tbilisi'],
        'GF' => ['America/Cayenne'],
        'GH' => ['Africa/Accra'],
        'GI' => ['Europe/Gibraltar'],
        'GL' => [
            'America/Godthab',
            'America/Danmarkshavn',
            'America/Scoresbysund',
            'America/Thule'
        ],
        'GM' => ['Africa/Banjul'],
        'GN' => ['Africa/Conakry'],
        'GP' => ['America/Guadeloupe'],
        'GQ' => ['Africa/Malabo'],
        'GR' => ['Europe/Athens'],
        'GS' => ['Atlantic/South_Georgia'],
        'GT' => ['America/Guatemala'],
        'GU' => ['Pacific/Guam'],
        'GW' => ['Africa/Bissau'],
        'GY' => ['America/Guyana'],
        'HK' => ['Asia/Hong_Kong'],
        'HN' => ['America/Tegucigalpa'],
        'HR' => ['Europe/Zagreb'],
        'HT' => ['America/Port-au-Prince'],
        'HU' => ['Europe/Budapest'],
        'ID' => [
            'Asia/Jakarta',
            'Asia/Pontianak',
            'Asia/Makassar',
            'Asia/Jayapura'
        ],
        'IE' => ['Europe/Dublin'],
        'IL' => ['Asia/Jerusalem'],
        'IN' => ['Asia/Calcutta'],
        'IO' => ['Indian/Chagos'],
        'IQ' => ['Asia/Baghdad'],
        'IR' => ['Asia/Tehran'],
        'IS' => ['Atlantic/Reykjavik'],
        'IT' => ['Europe/Rome'],
        'JM' => ['America/Jamaica'],
        'JO' => ['Asia/Amman'],
        'JP' => ['Asia/Tokyo'],
        'KE' => ['Africa/Nairobi'],
        'KG' => ['Asia/Bishkek'],
        'KH' => ['Asia/Phnom_Penh'],
        'KI' => [
            'Pacific/Tarawa',
            'Pacific/Enderbury',
            'Pacific/Kiritimati'
        ],
        'KM' => ['Indian/Comoro'],
        'KN' => ['America/St_Kitts'],
        'KP' => ['Asia/Pyongyang'],
        'KR' => ['Asia/Seoul'],
        'KW' => ['Asia/Kuwait'],
        'KY' => ['America/Cayman'],
        'KZ' => [
            'Asia/Almaty',
            'Asia/Qyzylorda',
            'Asia/Aqtobe',
            'Asia/Aqtau',
            'Asia/Oral'
        ],
        'LA' => ['Asia/Vientiane'],
        'LB' => ['Asia/Beirut'],
        'LC' => ['America/St_Lucia'],
        'LI' => ['Europe/Vaduz'],
        'LK' => ['Asia/Colombo'],
        'LR' => ['Africa/Monrovia'],
        'LS' => ['Africa/Maseru'],
        'LT' => ['Europe/Vilnius'],
        'LU' => ['Europe/Luxembourg'],
        'LV' => ['Europe/Riga'],
        'LY' => ['Africa/Tripoli'],
        'MA' => ['Africa/Casablanca'],
        'MC' => ['Europe/Monaco'],
        'MD' => ['Europe/Chisinau'],
        'MG' => ['Indian/Antananarivo'],
        'MH' => [
            'Pacific/Majuro',
            'Pacific/Kwajalein'
        ],
        'MK' => ['Europe/Skopje'],
        'ML' => [
            'Africa/Bamako',
            'Africa/Timbuktu'
        ],
        'MM' => ['Asia/Rangoon'],
        'MN' => [
            'Asia/Ulaanbaatar',
            'Asia/Hovd',
            'Asia/Choibalsan'
        ],
        'MO' => ['Asia/Macau'],
        'MP' => ['Pacific/Saipan'],
        'MQ' => ['America/Martinique'],
        'MR' => ['Africa/Nouakchott'],
        'MS' => ['America/Montserrat'],
        'MT' => ['Europe/Malta'],
        'MU' => ['Indian/Mauritius'],
        'MV' => ['Indian/Maldives'],
        'MW' => ['Africa/Blantyre'],
        'MX' => [
            'America/Mexico_City',
            'America/Cancun',
            'America/Merida',
            'America/Monterrey',
            'America/Mazatlan',
            'America/Chihuahua',
            'America/Hermosillo',
            'America/Tijuana'
        ],
        'MY' => [
            'Asia/Kuala_Lumpur',
            'Asia/Kuching'
        ],
        'MZ' => ['Africa/Maputo'],
        'NA' => ['Africa/Windhoek'],
        'NC' => ['Pacific/Noumea'],
        'NE' => ['Africa/Niamey'],
        'NF' => ['Pacific/Norfolk'],
        'NG' => ['Africa/Lagos'],
        'NI' => ['America/Managua'],
        'NL' => ['Europe/Amsterdam'],
        'NO' => ['Europe/Oslo'],
        'NP' => ['Asia/Katmandu'],
        'NR' => ['Pacific/Nauru'],
        'NU' => ['Pacific/Niue'],
        'NZ' => [
            'Pacific/Auckland',
            'Pacific/Chatham'
        ],
        'OM' => ['Asia/Muscat'],
        'PA' => ['America/Panama'],
        'PE' => ['America/Lima'],
        'PF' => [
            'Pacific/Tahiti',
            'Pacific/Marquesas',
            'Pacific/Gambier'
        ],
        'PG' => ['Pacific/Port_Moresby'],
        'PH' => ['Asia/Manila'],
        'PK' => ['Asia/Karachi'],
        'PL' => ['Europe/Warsaw'],
        'PM' => ['America/Miquelon'],
        'PN' => ['Pacific/Pitcairn'],
        'PR' => ['America/Puerto_Rico'],
        'PS' => ['Asia/Gaza'],
        'PT' => [
            'Europe/Lisbon',
            'Atlantic/Madeira',
            'Atlantic/Azores'
        ],
        'PW' => ['Pacific/Palau'],
        'PY' => ['America/Asuncion'],
        'QA' => ['Asia/Qatar'],
        'RE' => ['Indian/Reunion'],
        'RO' => ['Europe/Bucharest'],
        'RU' => [
            'Europe/Kaliningrad',
            'Europe/Moscow',
            'Europe/Samara',
            'Asia/Yekaterinburg',
            'Asia/Omsk',
            'Asia/Novosibirsk',
            'Asia/Krasnoyarsk',
            'Asia/Irkutsk',
            'Asia/Yakutsk',
            'Asia/Vladivostok',
            'Asia/Sakhalin',
            'Asia/Magadan',
            'Asia/Kamchatka',
            'Asia/Anadyr'
        ],
        'RW' => ['Africa/Kigali'],
        'SA' => ['Asia/Riyadh'],
        'SB' => ['Pacific/Guadalcanal'],
        'SC' => ['Indian/Mahe'],
        'SD' => ['Africa/Khartoum'],
        'SE' => ['Europe/Stockholm'],
        'SG' => ['Asia/Singapore'],
        'SH' => ['Atlantic/St_Helena'],
        'SI' => ['Europe/Ljubljana'],
        'SJ' => [
            'Arctic/Longyearbyen',
            'Atlantic/Jan_Mayen'
        ],
        'SK' => ['Europe/Bratislava'],
        'SL' => ['Africa/Freetown'],
        'SM' => ['Europe/San_Marino'],
        'SN' => ['Africa/Dakar'],
        'SO' => ['Africa/Mogadishu'],
        'SR' => ['America/Paramaribo'],
        'ST' => ['Africa/Sao_Tome'],
        'SV' => ['America/El_Salvador'],
        'SY' => ['Asia/Damascus'],
        'SZ' => ['Africa/Mbabane'],
        'TC' => ['America/Grand_Turk'],
        'TD' => ['Africa/Ndjamena'],
        'TF' => ['Indian/Kerguelen'],
        'TG' => ['Africa/Lome'],
        'TH' => ['Asia/Bangkok'],
        'TJ' => ['Asia/Dushanbe'],
        'TK' => ['Pacific/Fakaofo'],
        'TL' => ['Asia/Dili'],
        'TM' => ['Asia/Ashgabat'],
        'TN' => ['Africa/Tunis'],
        'TO' => ['Pacific/Tongatapu'],
        'TR' => ['Europe/Istanbul'],
        'TT' => ['America/Port_of_Spain'],
        'TV' => ['Pacific/Funafuti'],
        'TW' => ['Asia/Taipei'],
        'TZ' => ['Africa/Dar_es_Salaam'],
        'UA' => [
            'Europe/Kiev',
            'Europe/Uzhgorod',
            'Europe/Zaporozhye',
            'Europe/Simferopol'
        ],
        'UG' => ['Africa/Kampala'],
        'UM' => [
            'Pacific/Johnston',
            'Pacific/Midway',
            'Pacific/Wake'
        ],
        'US' => [
            'America/New_York',
            'America/Detroit',
            'America/Louisville',
            'America/Kentucky/Monticello',
            'America/Indianapolis',
            'America/Indiana/Marengo',
            'America/Indiana/Knox',
            'America/Indiana/Vevay',
            'America/Chicago',
            'America/Menominee',
            'America/North_Dakota/Center',
            'America/Denver',
            'America/Boise',
            'America/Shiprock',
            'America/Phoenix',
            'America/Los_Angeles',
            'America/Anchorage',
            'America/Juneau',
            'America/Yakutat',
            'America/Nome',
            'America/Adak',
            'Pacific/Honolulu'
        ],
        'UY' => ['America/Montevideo'],
        'UZ' => [
            'Asia/Samarkand',
            'Asia/Tashkent'
        ],
        'VA' => ['Europe/Vatican'],
        'VC' => ['America/St_Vincent'],
        'VE' => ['America/Caracas'],
        'VG' => ['America/Tortola'],
        'VI' => ['America/St_Thomas'],
        'VN' => ['Asia/Saigon'],
        'VU' => ['Pacific/Efate'],
        'WF' => ['Pacific/Wallis'],
        'WS' => ['Pacific/Apia'],
        'YE' => ['Asia/Aden'],
        'YT' => ['Indian/Mayotte'],
        'ZA' => ['Africa/Johannesburg'],
        'ZM' => ['Africa/Lusaka'],
        'ZW' => ['Africa/Harare']
    ];
}
