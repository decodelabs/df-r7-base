<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\lexer\processor;

use df;
use df\core;
use df\iris;
    
class Keyword implements iris\lexer\IProcessor, core\IDumpable {

    protected $_words = array();
    protected $_lowerWords = array();
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
        $this->_words = array();
        $this->_lowerWords = array();
        return $this;
    }

    public function isCaseSensitive($flag=null) {
        if($flag !== null) {
            $this->_isCaseSensitive = (bool)$flag;
            return $this;
        }

        return $this->_isCaseSensitive;
    }

    public function getName() {
        return 'Keyword';
    }

    public function getWeight() {
        return 1;
    }

    public function initialize(iris\lexer\ILexer $lexer) {
        if(empty($this->_words)) {
            throw new iris\lexer\LogicException(
                'Keyword processor does not have any words to match'
            );
        }
    }

    public function check(iris\lexer\ILexer $lexer) {
        return core\string\Util::isAlpha($lexer->char) || $lexer->char == '_';
    }

    public function run(iris\lexer\ILexer $lexer) {
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
