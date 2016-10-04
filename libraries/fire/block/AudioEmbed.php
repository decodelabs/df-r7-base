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

class AudioEmbed extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = ['Article', 'Description'];

    protected $_embedCode;

    public function getFormat() {
        return 'audio';
    }

    public function setEmbedCode($code) {
        $this->_embedCode = trim($code);
        return $this;
    }

    public function getEmbedCode() {
        return $this->_embedCode;
    }


    public function isEmpty() {
        return !strlen(trim($this->_embedCode));
    }

    public function getTransitionValue() {
        return $this->_embedCode;
    }

    public function setTransitionValue($value) {
        $this->_embedCode = $value;
        return $this;
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_embedCode = $reader->getFirstCDataSection();

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_embedCode);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $output = $this->getView()->html->audioEmbed($this->_embedCode);

        if($output) {
            $output = $output->render()
                ->addClass('block')
                ->setDataAttribute('type', $this->getName());
        }

        return $output;
    }
}