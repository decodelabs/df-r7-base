<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
    
class Symbol implements iris\IScanner, core\IDumpable {

    protected $_symbols = array();

    public function __construct($symbols=array()) {
        $this->setSymbols($symbols);
    }

    public function getName() {
        return 'Symbol';
    }

    public function getWeight() {
        return 1000;
    }

    public function setSymbols(array $symbols) {
        return $this->clearSymbols()
            ->addSymbols($symbols);
    }

    public function addSymbols(array $symbols) {
        foreach($symbols as $symbol) {
            $this->addSymbol($symbol);
        }

        return $this;
    }

    public function addSymbol($symbol) {
        $symbol = trim($symbol);

        if(!isset($this->_symbols[$symbol])) {
            $this->_symbols[$symbol] = 0;
        }

        return $this;
    }

    public function hasSymbol($symbol) {
        return isset($this->_symbols[$symbol]);
    }

    public function removeSymbol($symbol) {
        unset($this->_symbols[$symbol]);
        return $this;
    }

    public function clearSymbols() {
        $this->_symbols = array();
        return $this;
    }


    public function initialize(iris\ILexer $lexer) {
        if(empty($this->_symbols)) {
            throw new iris\LogicException(
                'Symbol processor does not have any symbols to match'
            );
        }

        uksort($this->_symbols, function($a, $b) {
            return mb_strlen($a) < mb_strlen($b);
        });
    }
    

    public function check(iris\ILexer $lexer) {
        return true;
    }

    public function run(iris\ILexer $lexer) {
        $symbols = array();

        foreach($this->_symbols as $symbol => $count) {
            if(mb_substr($symbol, 0, 1) != $lexer->char) {
                continue;
            }

            $length = mb_strlen($symbol);

            if($lexer->peek(0, $length) == $symbol) {
                $lexer->extract($length);
                $this->_symbols[$symbol]++;

                return $lexer->newToken('symbol', $symbol);
            }
        }
    }

// Dump
    public function getDumpProperties() {
        return [
            'symbols' => count($this->_symbols)
        ];
    }
}