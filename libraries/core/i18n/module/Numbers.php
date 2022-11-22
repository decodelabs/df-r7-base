<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\i18n\module;

use df\core;

class Numbers extends Base implements core\i18n\module\INumbersModule
{
    public function format($number, $format = null)
    {
        if ($format !== null) {
            $nf = new \NumberFormatter(
                (string)$this->_locale,
                \NumberFormatter::PATTERN_DECIMAL,
                (string)$format
            );
        } else {
            $nf = new \NumberFormatter(
                (string)$this->_locale,
                \NumberFormatter::DECIMAL
            );
        }

        $nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 10);
        return $nf->format($number);
    }

    public function parse($number, $type = self::DOUBLE, &$pos = 0, $format = null)
    {
        if ($format !== null) {
            $nf = new \NumberFormatter(
                (string)$this->_locale,
                \NumberFormatter::PATTERN_DECIMAL,
                (string)$format
            );
        } else {
            $nf = new \NumberFormatter(
                (string)$this->_locale,
                \NumberFormatter::DECIMAL
            );
        }

        return $nf->parse($number, $this->_formatParseType($type), $pos);
    }

    // Percent
    public function formatPercent($number, int $maxDigits = 3)
    {
        $nf = \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        );

        $nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDigits);
        return $nf->format($number / 100);
    }

    public function formatRatioPercent($number, int $maxDigits = 3)
    {
        $nf = \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        );

        $nf->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDigits);
        return $nf->format($number);
    }

    public function parsePercent($number)
    {
        return 100 * \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        )->parse((string)$number);
    }

    public function parseRatioPercent($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        )->parse((string)$number);
    }

    // Currency
    public function formatCurrency($amount, $code)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::CURRENCY
        )->formatCurrency($amount, $code);
    }

    public function formatCurrencyRounded($amount, $code)
    {
        $formatter = \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::CURRENCY
        );

        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $code);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        return $formatter->formatCurrency($amount, $code);
    }

    public function parseCurrency($amount, $code)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::CURRENCY
        )->parseCurrency($amount, $code);
    }

    public function getCurrencyName($code)
    {
        $this->_loadData();
        $code = strtoupper($code);

        if (isset($this->_data['currencies'][$code])) {
            return $this->_data['currencies'][$code]['name'];
        }

        return $code;
    }

    public function getCurrencySymbol($code, $amount = 1)
    {
        $this->_loadData();
        $code = strtoupper($code);

        if (isset($this->_data['currencies'][$code])) {
            $symbol = $this->_data['currencies'][$code]['symbol'];

            if (is_array($symbol)) {
                foreach ($symbol as $part) {
                    if (false !== ($pos = strpos($part, '<'))) {
                        $a = substr($part, 0, $pos);
                        $s = substr($part, $pos + 1);

                        if ($amount > $a) {
                            return $s;
                        }

                        continue;
                    } else {
                        $pos = strpos($part, '≤'); // @ignore-non-ascii
                        $t = strlen('≤') - 1; // @ignore-non-ascii
                        $a = substr($part, 0, (int)$pos);
                        $s = substr($part, $pos + $t + 1);

                        if ($amount == $a) {
                            return $s;
                        }

                        continue;
                    }
                }
            } else {
                return $symbol;
            }
        }

        return $code;
    }

    public function getCurrencyList()
    {
        $this->_loadData();
        $output = [];

        foreach ($this->_data['currencies'] as $code => $currency) {
            $output[$code] = $currency['name'];
        }

        asort($output);

        return $output;
    }

    public function isValidCurrency($code)
    {
        $this->_loadData();
        return isset($this->_data['currencies'][strtoupper($code)]);
    }

    // Scientific
    public function formatScientific($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SCIENTIFIC
        )->format($number);
    }

    public function parseScientific($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SCIENTIFIC
        )->parse($number);
    }

    // Spellout
    public function formatSpellout($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SPELLOUT
        )->format($number);
    }

    public function parseSpellout($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SPELLOUT
        )->parse($number);
    }

    // Ordinal
    public function formatOrdinal($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::ORDINAL
        )->format($number);
    }

    public function parseOrdinal($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::ORDINAL
        )->parse($number);
    }

    // Duration
    public function formatDuration($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::DURATION
        )->format($number);
    }

    public function parseDuration($number)
    {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::DURATION
        )->parse($number);
    }

    // Private
    private function _formatParseType($type)
    {
        switch ($type) {
            case self::INT32:
            case \NumberFormatter::TYPE_INT32:
                return \NumberFormatter::TYPE_INT32;

            case self::INT64:
            case \NumberFormatter::TYPE_INT64:
                return \NumberFormatter::TYPE_INT64;

            case self::DOUBLE:
            case \NumberFormatter::TYPE_DOUBLE:
            default:
                return \NumberFormatter::TYPE_DOUBLE;
        }
    }
}
