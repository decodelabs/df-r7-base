<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\writer;

use df;
use df\core;
    
class FirePhp implements core\log\IWriter {

    public static function isAvailable() {
        return false;
    }

    public function getId() {
        return 'FirePhp';
    }

    public function writeNode(core\log\IHandler $handler, core\log\INode $node) {
        
    }

    public function flush(core\log\IHandler $handler) {

    }
}