<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;

class TextNode extends iris\map\Node implements flex\latex\ITextNode, core\IDumpable {

    use flex\latex\TNodeClassProvider;

    public $text;

    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    public function appendText($text) {
        $this->text .= $text;
        return $this;
    }

    public function getText() {
        return $this->text;
    }

    public function isEmpty(): bool {
        return !strlen($this->text);
    }


// Dump
    public function getDumpProperties() {
        return $this->text;
    }
}
