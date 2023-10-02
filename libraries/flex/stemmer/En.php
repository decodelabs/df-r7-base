<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\stemmer;

class En extends Base
{
    public const CONSONANTS = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';
    public const VOWELS = '(?:[aeiou]|(?<![aeiou])y)';

    public function stem($phrase, $natural = false)
    {
        return $this->split($phrase, $natural);
    }

    public function split($phrase, $natural = false)
    {
        $phrase = (string)preg_replace('/[&][a-z]+[;]/', '', strtolower((string)$phrase));
        $phrase = str_replace(['-', '_'], ' ', $phrase);
        $phrase = str_replace(['.', '\''], '', $phrase);

        $words = str_word_count(strip_tags($phrase), 1);
        $temp = array_values((array)$words);
        $words = [];

        foreach ($temp as $key => $word) {
            if (!($word = $this->stemWord($word, $natural))) {
                continue;
            }

            $words[] = $word;
        }

        return $words;
    }


    public function stemWord($word, $natural = false)
    {
        if (strlen((string)$word) <= 4) {
            return $word;
        }

        $natural = (bool)$natural;

        $word = self::_step1($word, $natural);
        $word = self::_step2($word, $natural);
        $word = self::_step3($word, $natural);
        $word = self::_step4($word, $natural);
        $word = self::_step5($word, $natural);

        return $word;
    }

    protected static function _step1($word, $natural)
    {
        $v = self::VOWELS;

        if (substr($word, -1) == 's') {
            self::_replace($word, 'sses', 'ss') ||
            self::_replace($word, 'ies', 'i');//   ||
            //self::_replace($word, 'ss', 'ss');

            if (strlen((string)$word) > 4 || preg_match("#$v+#", substr($word, 0, 1))) {
                self::_replace($word, 's', '');
            }
        }

        if (substr($word, -2, 1) != 'e' || !self::_replace($word, 'eed', 'ee', 0)) {
            if (preg_match("#$v+#", substr($word, 0, -3)) && self::_replace($word, 'ing', '')
            || preg_match("#$v+#", substr($word, 0, -2)) && self::_replace($word, 'ed', '')) {
                if (!self::_replace($word, 'at', 'ate')
                && !self::_replace($word, 'bl', 'ble')
                && !self::_replace($word, 'iz', 'ize')
                && !self::_replace($word, 'is', 'ise')) {
                    if (self::_doubleConsonant($word)
                    && substr($word, -2) != 'll'
                    && substr($word, -2) != 'ss'
                    && substr($word, -2) != 'zz') {
                        $word = substr($word, 0, -1);
                    } elseif (self::_measure($word) == 1 && self::_cvc($word)) {
                        $word .= 'e';
                    }
                }
            }
        }

        //if(strlen((string)$word) > 4 && substr($word, -1) == 'y' && preg_match("#$v+#", substr($word, 0, -1))) {
        //self::_replace($word, 'y', 'i');
        //}

        return $word;
    }

    protected static function _step2($word, $natural)
    {
        switch (substr($word, -2, 1)) {
            case 'a':
                self::_replace($word, 'ational', 'ate', 0, !$natural)
                || self::_replace($word, 'tional', 'tion', 0);
                break;

            case 'c':
                self::_replace($word, 'enci', 'ence', 0, !$natural)
                || self::_replace($word, 'anci', 'ance', 0, !$natural)
                || self::_replace($word, 'enci', 'enc', 0, $natural)
                || self::_replace($word, 'anci', 'anc', 0, $natural);
                break;

            case 'e':
                self::_replace($word, 'izer', 'ize', 0)
                || self::_replace($word, 'iser', 'ise', 0);
                break;

            case 'g':
                self::_replace($word, 'logi', 'log', 0);
                break;

            case 'l':
                self::_replace($word, 'entli', 'ent', 0)
                || self::_replace($word, 'ousli', 'ous', 0)
                || self::_replace($word, 'alli', 'al', 0)
                || self::_replace($word, 'bli', 'ble', 0, !$natural)
                || self::_replace($word, 'bli', 'bl', 0, $natural)
                || self::_replace($word, 'eli', 'e', 0);
                break;

            case 'o':
                self::_replace($word, 'ization', 'ize', 0, !$natural)
                || self::_replace($word, 'isation', 'ise', 0, !$natural)
                || self::_replace($word, 'ation', 'ate', 0, !$natural)
                || self::_replace($word, 'ization', 'iz', 0, $natural)
                || self::_replace($word, 'isation', 'is', 0, $natural)
                || self::_replace($word, 'ation', 'at', 0, $natural);
                break;

            case 's':
                self::_replace($word, 'iveness', 'ive', 0)
                || self::_replace($word, 'fulness', 'ful', 0)
                || self::_replace($word, 'ousness', 'ous', 0)
                || self::_replace($word, 'alism', 'al', 0);
                break;

            case 't':
                self::_replace($word, 'biliti', 'ble', 0, !$natural)
                || self::_replace($word, 'aliti', 'al', 0)
                || self::_replace($word, 'iviti', 'ive', 0, !$natural)
                || self::_replace($word, 'biliti', 'bl', 0, $natural)
                || self::_replace($word, 'iviti', 'iv', 0, $natural);
                break;
        }
        return $word;
    }

    protected static function _step3($word, $natural)
    {
        switch (substr($word, -2, 1)) {
            case 'a':
                self::_replace($word, 'ical', 'ic', 0)
                || self::_replace($word, 'cial', 'ce', null, !$natural)
                || self::_replace($word, 'cial', 'c', null, $natural);
                break;

            case 's':
                self::_replace($word, 'ness', '', 0);
                break;

            case 't':
                self::_replace($word, 'icate', 'ic', 0)
                || self::_replace($word, 'iciti', 'ic', 0);
                break;

            case 'u':
                self::_replace($word, 'ful', '', 0);
                break;

            case 'v':
                self::_replace($word, 'ative', '', 0);
                break;

            case 'z':
                self::_replace($word, 'alize', 'al', 0)
                || self::_replace($word, 'alise', 'al', 0);
                break;
        }
        return $word;
    }

    protected static function _step4($word, $natural)
    {
        switch (substr($word, -2, 1)) {
            case 'a':
                $v = self::VOWELS;
                if (!preg_match("#$v+#", substr($word, 0, -3))) {
                    self::_replace($word, 'al', '', 1);
                }
                break;

            case 'c':
                self::_replace($word, 'ance', '', 1)
                || self::_replace($word, 'ence', '', 1);
                break;

            case 'e':
                self::_replace($word, 'er', '', 1);
                break;

            case 'i':
                self::_replace($word, 'ic', '', 1);
                break;

            case 'l':
                self::_replace($word, 'able', '', 1)
                || self::_replace($word, 'ible', '', 1);
                break;

            case 'n':
                self::_replace($word, 'ant', '', 1)
                || self::_replace($word, 'ement', '', 1)
                || self::_replace($word, 'ment', '', 1)
                || self::_replace($word, 'ent', '', 1);
                break;

            case 'o':
                if (substr($word, -4) == 'tion' || substr($word, -4) == 'sion') {
                    self::_replace($word, 'ion', '', 1);
                } else {
                    self::_replace($word, 'ou', '', 1);
                }
                break;

            case 's':
                self::_replace($word, 'ism', '', 1)
                || self::_replace($word, 'ise', '', 1);
                break;

            case 't':
                self::_replace($word, 'ate', '', 1)
                || self::_replace($word, 'iti', '', 1);
                break;

            case 'u':
                self::_replace($word, 'ous', '', 1);
                break;

            case 'v':
                self::_replace($word, 'ive', '', 1);
                break;

            case 'z':
                self::_replace($word, 'ize', '', 1);
                break;
        }
        return $word;
    }

    protected static function _step5($word, $natural)
    {
        if (substr($word, -1) == 'e') {
            if (self::_doubleConsonant(substr($word, 0, -1))
            || substr($word, -2, 1) == 'y') {
                self::_replace($word, 'e', '');
            }

            //if(self::_measure(substr($word, 0, -1)) > 1) {
            //self::_replace($word, 'e', '');
            //} else if(self::_measure(substr($word, 0, -1)) == 1) {
            //if(!self::_cvc(substr($word, 0, -1))) {
            //self::_replace($word, 'e', '');
            //}
            //}
        }

        if (self::_measure($word) > 1 && self::_doubleConsonant($word) && substr($word, -1) == 'l') {
            $word = substr($word, 0, -1);
        }

        return $word;
    }

    protected static function _replace(&$str, $check, $repl, $m = null, $natural = null)
    {
        if ($natural === false) {
            return false;
        }

        $len = 0 - strlen((string)$check);

        if (substr($str, $len) == $check) {
            $substr = substr($str, 0, $len);

            if (is_null($m) || self::_measure($substr) > $m) {
                $str = $substr . $repl;
            }

            return true;
        }

        return false;
    }

    protected static function _measure(string $str)
    {
        $c = self::CONSONANTS;
        $v = self::VOWELS;

        $str = (string)preg_replace("#^$c+#", '', $str);
        $str = (string)preg_replace("#$v+$#", '', $str);

        preg_match_all("#($v+$c+)#", $str, $matches);

        return count($matches[1]);
    }

    protected static function _doubleConsonant($str)
    {
        $c = self::CONSONANTS;
        return preg_match("#$c{2}$#", $str, $matches) && $matches[0][0] == $matches[0][1];
    }

    protected static function _cvc($str)
    {
        $c = self::CONSONANTS;
        $v = self::VOWELS;

        return preg_match("#($c$v$c)$#", $str, $matches)
            && strlen($matches[1]) == 3
            && $matches[1][2] != 'w'
            && $matches[1][2] != 'x'
            && $matches[1][2] != 'y';
    }
}
