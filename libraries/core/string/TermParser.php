<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df;
use df\core;

class TermParser implements ITermParser {
    
    protected $_locale;
    protected static $_stopWords = [
        'and', 'the', 'if', 'of', 'in', 'to', 'is', 'or', 'it', 'its', 'on', 'an'
    ];

    public function __construct($locale=null) {
        $this->_locale = core\i18n\Locale::factory($locale);
    }

    public function parse($phrase, $natural=false) {
        $terms = [];
        $phrase = strip_tags($phrase);
        $length = strlen($phrase);
        $pos = 0;
        $letters = [];

        while($pos < $length) {
            // Skip unwanted chars
            while($pos < $length && !$this->_testChar($phrase{$pos})) {
                $pos++;
            }

            // Buffer to next unwanted
            $start = $pos;

            while($pos < $length && $this->_testChar($phrase{$pos})) {
                $pos++;
            }

            if($pos == $start) {
                break;
            }

            $term = substr($phrase, $start, $pos - $start);

            if(null !== ($term = $this->_normalizeTerm($term, $natural))) {
                if(strlen($term) == 1) {
                    $letters[] = $term;
                } else {
                    $terms[] = $term;
                }
            }
        }

        $terms = array_unique($terms);
        $letters = array_unique($letters);

        if(count($terms) > 2) {
            $temp = $terms;

            foreach($terms as $i => $term) {
                if(in_array($term, self::$_stopWords)) {
                    unset($terms[$i]);
                }
            }

            if(empty($terms)) {
                $terms = $temp;
            }
        }

        if(empty($terms)) {
            $terms = $letters;
        }

        return $terms;
    }

    protected function _testChar($char) {
        return preg_match("/^[\p{L}|\p{N}\.\']$/u", $char);
    }

    protected function _normalizeTerm($term, $natural=false) {
        $term = strtolower(str_replace(['.', '\''], '', $term));

        if($stemmer = core\string\Util::loadStemmer($this->_locale)) {
            $term = $stemmer->stemWord($term, $natural);
        }

        return $term;
    }
}