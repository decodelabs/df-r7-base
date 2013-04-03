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
    
class Command implements iris\IScanner {

    public function getName() {
        return 'Command';
    }

    public function getWeight() {
        return 10;
    }

    public function initialize(iris\ILexer $lexer) {
        
    }

    public function check(iris\ILexer $lexer) {
        return $lexer->peek() == '\\';
    }

    public function run(iris\ILexer $lexer) {
        $lexer->extract();
        $command = '';
        $type = 'keyword';

        if(in_array($lexer->char, ['#', '$', '%', '^', '&', '_', '{', '}', '~', '\\'])) {
            $command = $lexer->extract();
            $type = 'symbol';
        } else {
            $command .= $lexer->extractRegexRange('a-zA-Z@');
        }

        if($lexer->char == '*') {
            $command .= $lexer->extract();
        }

        return $lexer->newToken('command/'.$type, $command);
    }
}