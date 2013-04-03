<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\scanner;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Word implements iris\IScanner {

    public function getName() {
        return 'Word';
    }

    public function getWeight() {
        return 1500;
    }

    public function initialize(iris\ILexer $lexer) {
        
    }

    public function check(iris\ILexer $lexer) {
        return $lexer->peekAlphanumeric() !== null;
    }

    public function run(iris\ILexer $lexer) {
        return $lexer->newToken('word', $lexer->extractAlphanumeric());
    }
}