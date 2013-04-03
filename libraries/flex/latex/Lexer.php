<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex;

use df;
use df\core;
use df\flex;
use df\iris;
    
class Lexer extends iris\Lexer {

    public function __construct(iris\ISource $source) {
        parent::__construct(
            $source,
            [
                new flex\latex\scanner\Command(),
                new flex\latex\scanner\Word(),
                new flex\latex\scanner\Symbol(),
                new iris\scanner\Comment(['%' => "\n"])
            ]
        );
    }
}