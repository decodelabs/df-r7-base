<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n\module;

class Countries extends Base implements ICountriesModule
{
    public const MODULE_NAME = 'countries';

    public function getName($id)
    {
        $this->_loadData();
        $id = strtoupper((string)$id);

        if (isset($this->_data[$id])) {
            return $this->_data[$id];
        }

        return $id;
    }

    public function getList(array $ids = null)
    {
        $this->_loadData();
        $output = $this->_data;

        if ($ids !== null) {
            $output = array_intersect_key($output, array_flip(array_values($ids)));
        }

        return $output;
    }

    public function getCodeList()
    {
        $this->_loadData();
        return array_keys($this->_data);
    }

    public function isValidId($id)
    {
        $this->_loadData();
        return isset($this->_data[$id]);
    }

    public function suggestCountryForLanguage($language)
    {
        $match = [];
        $language = strtolower((string)$language);

        switch ($language) {
            case 'en': return 'GB';
            case 'es': return 'ES';
            case 'fr': return 'FR';
        }

        $length = strlen($language);

        foreach (self::SUGGESTED_LOCALES as $locale) {
            if (substr($locale, 0, $length) == $language
            && strlen($locale) > $length) {
                $match[] = $locale;
            }
        }

        if (!empty($match)) {
            return substr((string)array_shift($match), 3);
        } else {
            return null;
        }
    }

    public const SUGGESTED_LOCALES = [
        'aa_DJ', 'aa_ER', 'aa_ET', 'aa', 'af_ZA', 'af', 'am_ET', 'am', 'ar_AE', 'ar_BH', 'ar_DZ', 'ar_EG',
        'ar_IQ', 'ar_JO', 'ar_KW', 'ar_LB', 'ar_LY', 'ar_MA', 'ar_OM', 'ar_QA', 'ar_SA', 'ar_SD', 'ar_SY',
        'ar_TN', 'ar_YE', 'ar', 'as_IN', 'as', 'az_AZ', 'az', 'be_BY', 'be', 'bg_BG', 'bg', 'bn_IN', 'bn',
        'bs_BA', 'bs', 'byn_ER', 'byn', 'ca_ES', 'ca', 'cs_CZ', 'cs', 'cy_GB', 'cy', 'da_DK', 'da', 'de_AT',
        'de_BE', 'de_CH', 'de_DE', 'de_LI', 'de_LU', 'de', 'dv_MV', 'dv', 'dz_BT', 'dz', 'el_CY', 'el_GR',
        'el', 'en_AS', 'en_AU', 'en_BE', 'en_BW', 'en_BZ', 'en_CA', 'en_GB', 'en_GU', 'en_HK', 'en_IE',
        'en_IN', 'en_JM', 'en_MH', 'en_MP', 'en_MT', 'en_NZ', 'en_PH', 'en_PK', 'en_SG', 'en_TT', 'en_UM',
        'en_US', 'en_VI', 'en_ZA', 'en_ZW', 'en', 'eo', 'es_AR', 'es_BO', 'es_CL', 'es_CO', 'es_CR', 'es_DO',
        'es_EC', 'es_ES', 'es_GT', 'es_HN', 'es_MX', 'es_NI', 'es_PA', 'es_PE', 'es_PR', 'es_PY', 'es_SV',
        'es_US', 'es_UY', 'es_VE', 'es', 'et_EE', 'et', 'eu_ES', 'eu', 'fa_AF', 'fa_IR', 'fa', 'fi_FI', 'fi',
        'fo_FO', 'fo', 'fr_BE', 'fr_CA', 'fr_CH', 'fr_FR', 'fr_LU', 'fr_MC', 'fr', 'ga_IE', 'ga', 'gez_ER',
        'gez_ET', 'gez', 'gl_ES', 'gl', 'gu_IN', 'gu', 'gv_GB', 'gv', 'haw_US', 'haw', 'he_IL', 'he', 'hi_IN',
        'hi', 'hr_HR', 'hr', 'hu_HU', 'hu', 'hy_AM', 'hy', 'id_ID', 'id', 'is_IS', 'is', 'it_CH', 'it_IT',
        'it', 'iu', 'ja_JP', 'ja', 'ka_GE', 'ka', 'kk_KZ', 'kk', 'kl_GL', 'kl', 'km_KH', 'km', 'kn_IN', 'kn',
        'ko_KR', 'ko', 'kok_IN', 'kok', 'kw_GB', 'kw', 'ky_KG', 'ky', 'lo_LA', 'lo', 'lt_LT', 'lt', 'lv_LV',
        'lv', 'mk_MK', 'mk', 'ml_IN', 'ml', 'mn_MN', 'mn', 'mr_IN', 'mr', 'ms_BN', 'ms_MY', 'ms', 'mt_MT',
        'mt', 'nb_NO', 'nb', 'nl_BE', 'nl_NL', 'nl', 'no_NO', 'no', 'om_ET', 'om_KE', 'om', 'or_IN', 'or',
        'pa_IN', 'pa', 'pl_PL', 'pl', 'ps_AF', 'ps', 'pt_BR', 'pt_PT', 'pt', 'ro_RO', 'ro', 'ru_RU', 'ru_UA',
        'ru', 'sa_IN', 'sa', 'sh_BA', 'sh_CS', 'sh_YU', 'sh', 'sid_ET', 'sid', 'sk_SK', 'sk', 'sl_SI', 'sl',
        'so_DJ', 'so_ET', 'so_KE', 'so_SO', 'so', 'sq_AL', 'sq', 'sr_BA', 'sr_CS', 'sr_YU', 'sr', 'sv_FI',
        'sv_SE', 'sv', 'sw_KE', 'sw_TZ', 'sw', 'syr_SY', 'syr', 'ta_IN', 'ta', 'te_IN', 'te', 'th_TH', 'th',
        'ti_ER', 'ti_ET', 'ti', 'tig_ER', 'tig', 'tr_TR', 'tr', 'tt_RU', 'tt', 'uk_UA', 'uk', 'ur_PK', 'ur',
        'uz_AF', 'uz_UZ', 'uz', 'vi_VN', 'vi', 'wal_ET', 'wal', 'zh_CN', 'zh_HK', 'zh_MO', 'zh_SG', 'zh_TW', 'zh'
    ];
}
