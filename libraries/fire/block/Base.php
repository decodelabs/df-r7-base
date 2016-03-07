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
use df\arch;
use df\flex;

abstract class Base implements IBlock {

    use core\xml\TReaderInterchange;
    use core\xml\TWriterInterchange;
    use aura\view\TDeferredRenderable;
    use core\TStringProvider;

    const VERSION = 1;

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = [];

    protected $_isNested = false;

    public static function fromXmlElement(core\xml\ITree $element) {
        $output = self::factory($element->getAttribute('type'));
        $output->readXml($element);

        return $output;
    }

    public static function factory($name) {
        if($name instanceof IBlock) {
            return $name;
        }

        $class = 'df\\fire\\block\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Block type '.$name.' could not be found'
            );
        }

        return new $class();
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getDisplayName() {
        return flex\Text::formatName($this->getName());
    }

    public function getVersion() {
        return static::VERSION;
    }

    public function isNested(bool $flag=null) {
        if($flag !== null) {
            $this->_isNested = $flag;
            return $this;
        }

        return $this->_isNested;
    }


    public static function getOutputTypes() {
        return (array)static::OUTPUT_TYPES;
    }

    public function canOutput($outputType) {
        if(empty(static::OUTPUT_TYPES)) {
            return true;
        }

        $outputType = strtolower($outputType);

        foreach($this->getOutputTypes() as $type) {
            if($outputType == strtolower($type)) {
                return true;
            }
        }

        return false;
    }

    public function getFormat() {
        return 'text';
    }

    public function isHidden() {
        return false;
    }

    public function getFormDelegateName() {
        return $this->getName();
    }

    public static function getDefaultCategories() {
        return (array)static::DEFAULT_CATEGORIES;
    }

    public function toString() {
        return $this->render();
    }

    public function getTransitionValue() {
        return null;
    }

    public function setTransitionValue($value) {
        return $this;
    }


    protected function _validateXmlReader(core\xml\IReadable $reader) {
        if($reader->getTagName() != 'block') {
            throw new UnexpectedValueException(
                'Block content object expected block xml element'
            );
        }

        if($reader->hasAttribute('wrap')) {
            $type = $reader->getAttribute('wrap');
        } else {
            $type = $reader->getAttribute('type');
        }

        if(strtolower($type) != strtolower($this->getName())) {
            throw new UnexpectedValueException(
                'Block content is meant for a '.$reader->getAttribute('type').' block, not a '.$this->getName().' block'
            );
        }

        $this->_isNested = $reader->getBooleanAttribute('nested');
    }


    protected function _startWriterBlockElement(core\xml\IWriter $writer) {
        $writer->startElement('block');
        $writer->setAttribute('version', $this->getVersion());
        $writer->setAttribute('type', $this->getName());

        if($this->_isNested) {
            $writer->setAttribute('nested', true);
        }
    }

    protected function _endWriterBlockElement(core\xml\IWriter $writer) {
        // TODO: End any unclosed child elements

        $writer->endElement();
    }
}