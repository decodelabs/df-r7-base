<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;

class Element extends Tag implements IElement, core\IDumpable {
    
    use TElementContent;
    use core\string\THtmlStringEscapeHandler;
    
    public function __construct($name, $content=null, array $attributes=array()) {
        parent::__construct($name, $attributes);
        $this->import($content);
    }
    
    public function toString() {
        return (string)$this->renderWith($this);
    }
    
    public function getDumpProperties() {
        return $this->toString();
    }
}