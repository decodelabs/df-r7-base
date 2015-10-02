<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;
use df\flex;

class Element extends Tag implements IElement, core\IDumpable {

    use TElementContent;
    use flex\THtmlStringEscapeHandler;

    public function __construct($name, $content=null, array $attributes=null) {
        parent::__construct($name, $attributes);

        if($content !== null) {
            $this->import($content);
        }
    }

    public function toString() {
        return (string)$this->renderWith($this);
    }

    public function render($expanded=false) {
        return $this->renderWith($this, $expanded);
    }


// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}