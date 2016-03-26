<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\flex;
use df\aura;

class Markdown extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = ['Description'];

    protected $_body;

    public function getFormat() {
        return 'markup';
    }

    public function setBody($body) {
        $this->_body = trim($body);
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }


    public function isEmpty() {
        return !strlen(trim($this->_body));
    }

    public function getTransitionValue() {
        return $this->_body;
    }

    public function setTransitionValue($value) {
        $this->_body = $value;
        return $this;
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_body = $reader->getFirstCDataSection();
        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_body);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $view = $this->getView();

        return $view->html('div.block', $view->html->markdown($this->_body))
            ->setDataAttribute('type', $this->getName());
    }
}