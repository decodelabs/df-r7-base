<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\parser\processor;

use df;
use df\core;
use df\iris;
    
abstract class Base implements iris\parser\IProcessor {

    public $parser;

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function initialize(iris\parser\IParser $parser) {
        $this->parser = $parser;
    }
}