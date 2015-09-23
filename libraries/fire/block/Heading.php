<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\aura;
    
class Heading extends Base {

    protected static $_outputTypes = ['Html'];
    protected static $_defaultCategories = ['Description'];

    protected $_heading;
    protected $_level = 3;

    public function getDisplayName() {
        return 'Heading';
    }

    public function getFormat() {
        return 'structure';
    }

// Heading
    public function setHeading($heading) {
        $this->_heading = $heading;
        return $this;
    }

    public function getHeading() {
        return $this->_heading;
    }

    public function setHeadingLevel($level) {
        $this->_level = (int)$level;

        if($this->_level < 1) {
            $this->_level = 1;
        } else if($this->_level > 6) {
            $this->_level = 6;
        }

        return $this;
    }

    public function getHeadingLevel() {
        return $this->_level;
    }

// IO
    public function isEmpty() {
        return !strlen(trim($this->_heading));
    }

    public function getTransitionValue() {
        return $this->_heading;
    }

    public function setTransitionValue($value) {
        $this->_heading = str_replace("\n", ' ', $value);
        return $this;
    }

    public function readXml(core\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_heading = $reader->getFirstCDataSection();
        $this->_level = $reader->getAttribute('level');
        
        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        
        $writer->setAttribute('level', $this->_level);
        $writer->writeCData($this->_heading);

        $this->_endWriterBlockElement($writer);
        return $this;
    }

// Render
    public function render() {
        return $this->getView()->html('h'.$this->_level.'.block', $this->_heading)
            ->setDataAttribute('type', $this->getName());
    }
}