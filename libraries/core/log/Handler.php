<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log;

use df;
use df\core;
    
class Handler implements IHandler {

    use TWriterProvider;
    use TEntryPoint;

    public function addNode(core\log\INode $node) {
        foreach($this->_writers as $writer) {
            $writer->writeNode($this, $node);
        }

        return $this;
    }

    public function flush() {
        foreach($this->_writers as $writer) {
            $writer->flush($this);
        }

        return $this;
    }

    public function __destruct() {
        $this->flush();
    }
}