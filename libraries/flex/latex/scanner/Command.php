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

    const SYMBOL_COMMANDS = [
        '\\', '`', '\'', '^', '"', '~', '=', '.'
    ];

    public function getName(): string {
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

        if(in_array($lexer->char, self::SYMBOL_COMMANDS)) {
            $command = $lexer->extract();
        } else if($lexer->char == '@' || $lexer->peekAlpha()) {
            $command = $lexer->extractRegexRange('a-zA-Z@');
        } else {
            return $lexer->newToken('symbol/esc', $lexer->extract());
        }

        if($lexer->char == '*') {
            $command .= $lexer->extract();
        }

        return $lexer->newToken('command', $command);
    }
}
