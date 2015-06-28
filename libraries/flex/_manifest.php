<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use df;
use df\core;
use df\flex;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IParser {
    public function setSource($source);
    public function getSource();
}


trait TParser {

    public $source;

    public function __construct($source) {
        $this->setSource($source);
    }

    public function setSource($source) {
        $this->source = (string)$source;
        return $this;
    }

    public function getSource() {
        return $this->source;
    }
}


interface IHtmlProducer extends IParser {
    public function toHtml();
}

interface IInlineHtmlProducer extends IHtmlProducer {
    public function toInlineHtml();
}

interface ITextProducer extends IParser {
    public function toText();
}