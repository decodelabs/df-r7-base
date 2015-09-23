<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\aura;
    
class SimpleTags extends Base {

    protected static $_outputTypes = ['Html'];
    protected static $_defaultCategories = ['Description'];

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

    public function readXml(core\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_body = $reader->getFirstCDataSection();
        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_body);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


    public function render() {
        $view = $this->getView();
        return $view->html('div.block', $view->html->simpleTags($this->_body))
            ->setDataAttribute('type', $this->getName());
    }
}