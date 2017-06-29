<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
use df\flex;

class Keyword implements iris\IScanner, core\IDumpable {

    protected $_words = [];
    protected $_lowerWords = [];
    protected $_isCaseSensitive = false;
    protected $_defaultWordType = 'word';

    public function __construct(array $words, $isCaseSensitive=false) {
        $this->setWords($words);
        $this->isCaseSensitive($isCaseSensitive);
    }

    public function setDefaultWordType($type) {
        $this->_defaultWordType = $type;
        return $this;
    }

    public function getDefaultWordType() {
        return $this->_defaultWordType;
    }


    public function setWords(array $words) {
        return $this->clearWords()
            ->addWords($words);
    }

    public function addWords(array $words) {
        foreach($words as $word) {
            $this->addWord($word);
        }

        return $this;
    }

    public function addWord($word) {
        $word = trim($word);

        if(isset($this->_words[$word])) {
            return $this;
        }

        $this->_words[$word] = 0;
        $this->_lowerWords[$word] = 0;

        return $this;
    }

    public function removeWord($word) {
        unset($this->_words[$word], $this->_lowerWords[strtolower($word)]);
        return $this;
    }

    public function clearWords() {
        $this->_words = [];
        $this->_lowerWords = [];
        return $this;
    }

    public function isCaseSensitive(bool $flag=null) {
        if($flag !== null) {
            $this->_isCaseSensitive = $flag;
            return $this;
        }

        return $this->_isCaseSensitive;
    }

    public function getName(): string {
        return 'Keyword';
    }

    public function getWeight() {
        return 1;
    }

    public function initialize(iris\ILexer $lexer) {
        if(empty($this->_words)) {
            throw new iris\LogicException(
                'Keyword scanner does not have any words to match'
            );
        }
    }

    public function check(iris\ILexer $lexer) {
        return flex\Text::isAlpha($lexer->char) || $lexer->char == '_';
    }

    public function run(iris\ILexer $lexer) {
        $word = $lexer->extractRegexRange('a-zA-Z0-9_');

        if($this->_isCaseSensitive && isset($this->_words[$word])) {
            $this->_words[$word]++;
            return $lexer->newToken('keyword', $word);
        } else if(!$this->_isCaseSensitive && isset($this->_lowerWords[strtolower($word)])) {
            $this->_lowerWords[strtolower($word)]++;
            return $lexer->newToken('keyword', strtolower($word));
        } else {
            return $lexer->newToken($this->_defaultWordType, $word);
        }
    }


// Dump
    public function getDumpProperties() {
        return [
            'caseSensitive' => $this->_isCaseSensitive,
            'defaultWordType' => $this->_defaultWordType,
            'words' => count($this->_words)
        ];
    }
}
