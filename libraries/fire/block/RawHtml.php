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
use df\arch;

class RawHtml extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = ['Description'];

    protected $_content;

    public function getFormat() {
        return 'markup';
    }

    public function setHtmlContent($content) {
        $this->_content = trim($content);
        return $this;
    }

    public function getHtmlContent() {
        return $this->_content;
    }

    public function isEmpty() {
        return !strlen(trim($this->_content));
    }

    public function getTransitionValue() {
        return $this->_content;
    }

    public function setTransitionValue($value) {
        $this->_content = $value;
        return $this;
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_content = $reader->getFirstCDataSection();
        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_content);
        $this->_endWriterBlockElement($writer);

        return $this;
    }

    public function render() {
        $view = $this->getView();

        $content = preg_replace_callback('/ (href|src)\=\"([^\"]+)\"/', function($matches) use($view) {
            return ' '.$matches[1].'="'.$view->uri->__invoke($matches[2]).'"';
        }, $this->_content);

        return $view->html('div.block', $view->html->string($content))
            ->setDataAttribute('type', $this->getName());
    }
}