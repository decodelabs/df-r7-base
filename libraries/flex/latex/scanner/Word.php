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

class Word implements iris\IScanner
{
    public function getName(): string
    {
        return 'Word';
    }

    public function getWeight()
    {
        return 1500;
    }

    public function initialize(iris\Lexer $lexer)
    {
    }

    public function check(iris\Lexer $lexer)
    {
        return $lexer->peekAlphanumeric() !== null;
    }

    public function run(iris\Lexer $lexer)
    {
        return $lexer->newToken('word', $lexer->extractAlphanumeric());
    }
}
