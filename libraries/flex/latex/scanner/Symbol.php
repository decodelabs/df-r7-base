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
    
class Symbol implements iris\IScanner {

    public function getName() {
        return 'Symbol';
    }

    public function getWeight() {
        return 1000;
    }

    public function initialize(iris\ILexer $lexer) {
        
    }

    public function check(iris\ILexer $lexer) {
        return $lexer->peekAlphanumeric() === null;
    }

    public function run(iris\ILexer $lexer) {
        $symbol = $lexer->extract();
        $type = 'passive';

        if(in_array($symbol, ['#', '$', '^', '&', '_', '{', '}', '~'])) {
            $type = 'active';
        }

        return $lexer->newToken('symbol/'.$type, $symbol);
    }
}