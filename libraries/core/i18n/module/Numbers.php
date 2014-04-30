<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Numbers extends Base implements core\i18n\module\generator\IModule {
    
    const INT32 = 'int32';
    const INT64 = 'int64';
    const DOUBLE = 'double';
    
    public function format($number, $format=null) {
        if($format !== null) {
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
        
        return $nf->format($number);
    }
    
    public function parse($number, $type=self::DOUBLE, &$pos=0, $format=null) {
        if($format !== null) {
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
    public function formatPercent($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        )->format($number);
    }
    
    public function parsePercent($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::PERCENT
        )->parse($number);    
    }
    
// Currency
    public function formatCurrency($amount, $code) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::CURRENCY
        )->formatCurrency($amount, $code);
    }
    
    public function parseCurrency($amount, $code) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::CURRENCY
        )->parseCurrency($amount, $code);
    }
    
    public function getCurrencyName($code) {
        $this->_loadData();
        $code = strtoupper($code);
        
        if(isset($this->_data['currencies'][$code])) {
            return $this->_data['currencies'][$code]['name'];    
        }    
        
        return $code;
    } 
    
    public function getCurrencySymbol($code, $amount=1) {
        $this->_loadData();
        $code = strtoupper($code);
        
        if(isset($this->_data['currencies'][$code])) {
            $symbol = $this->_data['currencies'][$code]['symbol'];
            
            if(is_array($symbol)) {
                foreach($symbol as $part) {
                    if(false !== ($pos = strpos($part, '<'))) {
                        $a = substr($part, 0, $pos);
                        $s = substr($part, $pos+1);  
                        
                        if($amount > $a) {
                            return $s;    
                        }
                        
                        continue;
                    } else {
                        $pos = strpos($part, '≤');
                        $t = strlen('≤')-1;
                        $a = substr($part, 0, $pos);
                        $s = substr($part, $pos + $t + 1);  
                        
                        if($amount == $a) {
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
        
    public function getCurrencyList() {
        $this->_loadData();
        $output = [];
        
        foreach($this->_data['currencies'] as $code => $currency) {
            $output[$code] = $currency['name'];
        }    
        
        asort($output);
        
        return $output;
    }
    
    public function isValidCurrency($code) {
        $this->_loadData();
        return isset($this->_data['currencies'][strtoupper($code)]);    
    }
    
// Scientific
    public function formatScientific($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SCIENTIFIC
        )->format($number);
    }
    
    public function parseScientific($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SCIENTIFIC
        )->parse($number);
    }
    
// Spellout
    public function formatSpellout($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SPELLOUT
        )->format($number);
    }
    
    public function parseSpellout($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::SPELLOUT
        )->parse($number);
    }
    
// Ordinal
    public function formatOrdinal($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::ORDINAL
        )->format($number);
    }
    
    public function parseOrdinal($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::ORDINAL
        )->parse($number);
    }
    
// Duration
    public function formatDuration($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::DURATION
        )->format($number);
    }
    
    public function parseDuration($number) {
        return \NumberFormatter::create(
            (string)$this->_locale,
            \NumberFormatter::DURATION
        )->parse($number);
    }
    
// File size
    public function formatFileSize($bytes, $precision=2, $longNames=false) {
        $unit = 1;
        
        while(true) {
            if($bytes == 0) {
                break;    
            }
            
            if($bytes < 1 || $unit > 6) {
                $bytes *= 1024;
                $unit--;
                break;    
            }
            
            $bytes /= 1024;
            $unit++;
        }
        
        $output = $this->format(round($bytes, $precision));
        $translate = core\i18n\translate\Handler::factory('core\\i18n');
        
        if($longNames) {
            switch($unit) {
                case 1: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% byte',
                            '*' => '%n% bytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
                
                case 2: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% Kilobyte',
                            '*' => '%n% Kilobytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
                
                case 3: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% Megabyte',
                            '*' => '%n% Megabytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
                
                case 4: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% Gigabyte',
                            '*' => '%n% Gigabytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
                
                case 5: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% Terabyte',
                            '*' => '%n% Terabytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
                
                case 6: 
                    return $translate->_(
                        [
                            'n = 1 || n = -1' => '%n% Petabyte',
                            '*' => '%n% Petabytes'
                        ],
                        ['%n%' => $output],
                        $output
                    );
            }
        } else {
            switch($unit) {
                case 1: 
                    return $translate->_('%n% b', ['%n%' => $output]);
                
                case 2: 
                    return $translate->_('%n% Kb', ['%n%' => $output]);
                
                case 3: 
                    return $translate->_('%n% Mb', ['%n%' => $output]);
                
                case 4: 
                    return $translate->_('%n% Gb', ['%n%' => $output]);
                
                case 5: 
                    return $translate->_('%n% Tb', ['%n%' => $output]);
                
                case 6: 
                    return $translate->_('%n% Pb', ['%n%' => $output]);
            }    
        }
    }
    
// Private
    private function _formatParseType($type) {
        switch($type) {
            case self::INT32:
            case \NumberFormatter::TYPE_INT32:
                return \NumberFormatter::TYPE_INT32;
                
            case self::INT64:
            case \NumberFormatter::TYPE_INT64:
                return \NumberFormatter::TYPE_INT64;
                
            case self::DOUBLE:
            case \NumberFormatter::DOUBLE:
            default:
                return \NumberFormatter::TYPE_DOUBLE;    
        }
    }
    

// Generator
    public function _convertCldr(core\i18n\ILocale $locale, \SimpleXMLElement $doc) {
        $output = null;
        
        if(!isset($doc->numbers)) {
            return $output;    
        }
        
        $output = [
            'symbols' => [],
            'formats' => [],
            'currencies' => []
        ];
        
        // Symbols
        if(isset($doc->numbers->symbols)) {
            foreach($doc->numbers->symbols->children() as $tag => $symbol) {
                $output['symbols'][$tag] = (string)$symbol;    
            }
        }
        
        // Decimal
        if(isset($doc->numbers->decimalFormats->decimalFormatLength->decimalFormat->pattern)) {
            $output['formats']['decimal'] = (string)$doc->numbers->decimalFormats->decimalFormatLength->decimalFormat->pattern;  
        }
        
        // Scientific
        if(isset($doc->numbers->scientificFormats->scientificFormatLength->scientificFormat->pattern)) {
            $output['formats']['scientific'] = (string)$doc->numbers->scientificFormats->scientificFormatLength->scientificFormat->pattern;  
        }
        
        // Percent
        if(isset($doc->numbers->percentFormats->percentFormatLength->percentFormat->pattern)) {
            $output['formats']['percent'] = (string)$doc->numbers->percentFormats->percentFormatLength->percentFormat->pattern;  
        }
        
        // Currency Format
        if(isset($doc->numbers->currencyFormats->currencyFormatLength->currencyFormat->pattern)) {
            $output['formats']['currency'] = (string)$doc->numbers->currencyFormats->currencyFormatLength->currencyFormat->pattern;  
        }
        
        
        // Currencies
        if(isset($doc->numbers->currencies)) {
            foreach($doc->numbers->currencies->currency as $currency) {
                $symbol = (string)$currency->symbol;
                if(!strlen($symbol)) {
                    $symbol = (string)$currency['type'];    
                }
                if($currency->symbol['choice'] == 'true') {
                    $symbol = explode('|', $symbol);
                }
                
                $name = (string)$currency->displayName;
                if(!strlen($name)) {
                    $name = (string)$currency['type'];    
                }
                
                $output['currencies'][(string)$currency['type']] = [
                    'name' => $name,
                    'symbol' => $symbol
                ];    
            }    
            
            ksort($output['currencies']);
        }
        
        return $output; 
    }
}
