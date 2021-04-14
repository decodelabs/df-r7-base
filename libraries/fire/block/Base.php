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
use df\arch;

use DecodeLabs\Exemplar\Consumer as XmlConsumer;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\Exemplar\Serializable as XmlSerializable;
use DecodeLabs\Exemplar\SerializableTrait as XmlSerializableTrait;
use DecodeLabs\Dictum;
use DecodeLabs\Glitch;
use DecodeLabs\Exceptional;

abstract class Base implements fire\IBlock
{
    use XmlSerializableTrait;
    use aura\view\TView_DeferredRenderable;
    use core\TStringProvider;

    const VERSION = 1;
    const DEFAULT_CATEGORIES = [];

    protected $_isNested = false;

    public static function fromXmlElement(XmlElement $element): XmlConsumer
    {
        if (null === ($type = $element->getAttribute('type'))) {
            throw Exceptional::UnexpectedValue(
                'Block XML does not contain type attribute', null, $element
            );
        }

        $output = self::factory($type);

        if (!$output instanceof XmlSerializable) {
            throw Exceptional::UnexpectedValue(
                'Block object is not instanceof XmlSerializable', null, $output
            );
        }

        $output->xmlUnserialize($element);
        return $output;
    }

    public static function factory(string $name): fire\IBlock
    {
        $class = 'df\\fire\\block\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Block type '.$name.' could not be found'
            );
        }

        return new $class();
    }

    public static function normalize($block): ?fire\IBlock
    {
        if ($block instanceof fire\IBlock || $block === null) {
            return $block;
        }

        return static::factory($block);
    }



    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function getDisplayName(): string
    {
        return Dictum::name($this->getName());
    }

    public function getVersion(): int
    {
        return static::VERSION;
    }

    public function isNested(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isNested = $flag;
            return $this;
        }

        return $this->_isNested;
    }


    public function getFormat(): string
    {
        return 'text';
    }

    public function isHidden(): bool
    {
        return false;
    }

    public static function getDefaultCategories(): array
    {
        return (array)static::DEFAULT_CATEGORIES;
    }

    public function toString(): string
    {
        return (string)$this->render();
    }

    public function getTransitionValue()
    {
        return null;
    }

    public function setTransitionValue($value)
    {
        return $this;
    }


    public function xmlUnserialize(XmlElement $element): void
    {
        $this->_validateXmlReader($element);
        $this->readXml($element);
    }

    public function xmlSerialize(XmlWriter $writer): void
    {
        $this->_startWriterBlockElement($writer);
        $this->writeXml($writer);
        $this->_endWriterBlockElement($writer);
    }


    protected function readXml(XmlElement $element): void
    {
        Glitch::incomplete();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        Glitch::incomplete();
    }


    protected function _validateXmlReader(/*XmlElement*/ $reader)
    {
        if ($reader->getTagName() != 'block') {
            throw Exceptional::UnexpectedValue(
                'Block content object expected block xml element'
            );
        }

        if ($reader->hasAttribute('wrap')) {
            $type = $reader->getAttribute('wrap');
        } else {
            $type = $reader->getAttribute('type');
        }

        if (strtolower($type) != strtolower($this->getName())) {
            throw Exceptional::UnexpectedValue(
                'Block content is meant for a '.$reader->getAttribute('type').' block, not a '.$this->getName().' block'
            );
        }

        $this->_isNested = $reader->getBooleanAttribute('nested');
    }


    protected function _startWriterBlockElement(/*XmlWriter*/ $writer)
    {
        $writer->startElement('block');
        $writer->setAttribute('version', $this->getVersion());
        $writer->setAttribute('type', $this->getName());

        if ($this->_isNested) {
            $writer->setAttribute('nested', true);
        }
    }

    protected function _endWriterBlockElement(/*XmlWriter*/ $writer)
    {
        $writer->endElement();
    }
}



// Form delegate
abstract class Base_Delegate extends arch\node\form\Delegate implements fire\IBlockDelegate
{
    use arch\node\TForm_InlineFieldRenderableDelegate;
    use core\constraint\TRequirable;

    protected $_isNested = false;
    protected $_block;

    public function __construct(fire\IBlock $block, arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, $id)
    {
        $this->_block = $block;
        parent::__construct($context, $state, $event, $id);
    }

    public function setBlock(fire\IBlock $block)
    {
        $this->_block = $block;
        return $this;
    }

    public function getBlock()
    {
        return $this->_block;
    }

    public function isNested(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isNested = $flag;
            return $this;
        }

        return $this->_isNested;
    }
}
