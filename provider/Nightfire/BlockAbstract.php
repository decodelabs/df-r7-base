<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use DecodeLabs\Archetype;
use DecodeLabs\Dictum;

use DecodeLabs\Exceptional;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Serializable as XmlSerializable;
use DecodeLabs\Exemplar\SerializableTrait as XmlSerializableTrait;

use DecodeLabs\Exemplar\Writer as XmlWriter;
use df\arch\IContext as Context;
use df\arch\node\form\State as FormState;
use df\arch\node\IDelegate as NodeDelegate;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\aura\view\TView_DeferredRenderable;
use df\core\TStringProvider;

abstract class BlockAbstract implements Block
{
    use XmlSerializableTrait;
    use TView_DeferredRenderable;
    use TStringProvider;

    public const VERSION = 1;
    public const DEFAULT_CATEGORIES = [];

    protected bool $_isNested = false;

    public static function fromXmlElement(XmlElement $element): static
    {
        if (null === ($type = $element->getAttribute('type'))) {
            throw Exceptional::UnexpectedValue(
                'Block XML does not contain type attribute',
                null,
                $element
            );
        }

        $output = self::factory($type);

        if (!$output instanceof XmlSerializable) {
            throw Exceptional::UnexpectedValue(
                'Block object is not instanceof XmlSerializable',
                null,
                $output
            );
        }

        $output->xmlUnserialize($element);
        /**
         * @var static
         */
        return $output;
    }

    public static function factory(string $name): Block
    {
        $class = Archetype::resolve(Block::class, $name);
        return new $class();
    }

    public static function normalize(
        Block|string|null $block
    ): ?Block {
        if (
            $block instanceof Block ||
            $block === null
        ) {
            return $block;
        }

        return self::factory($block);
    }



    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function getDisplayName(): string
    {
        return (string)Dictum::name($this->getName());
    }

    public function getVersion(): int
    {
        return static::VERSION;
    }

    /**
     * Set nested
     */
    public function setNested(bool $nested): static
    {
        $this->_isNested = $nested;
        return $this;
    }

    /**
     * Is nested
     */
    public function isNested(): bool
    {
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

    public function getTransitionValue(): mixed
    {
        return null;
    }

    public function setTransitionValue(mixed $value): static
    {
        return $this;
    }


    public function xmlUnserialize(XmlElement $element): void
    {
        $this->validateXmlReader($element);
        $this->readXml($element);
    }

    public function xmlSerialize(XmlWriter $writer): void
    {
        $this->startWriterBlockElement($writer);
        $this->writeXml($writer);
        $this->endWriterBlockElement($writer);
    }


    abstract protected function readXml(XmlElement $element): void;
    abstract protected function writeXml(XmlWriter $writer): void;


    protected function validateXmlReader(XmlElement $reader): void
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

        if (strtolower((string)$type) != strtolower($this->getName())) {
            throw Exceptional::UnexpectedValue(
                'Block content is meant for a ' . $reader->getAttribute('type') . ' block, not a ' . $this->getName() . ' block'
            );
        }

        $this->_isNested = $reader->getBooleanAttribute('nested');
    }


    protected function startWriterBlockElement(XmlWriter $writer): void
    {
        $writer->startElement('block');
        $writer->setAttribute('version', $this->getVersion());
        $writer->setAttribute('type', $this->getName());

        if ($this->_isNested) {
            $writer->setAttribute('nested', true);
        }
    }

    protected function endWriterBlockElement(XmlWriter $writer): void
    {
        $writer->endElement();
    }


    public function loadFormDelegate(
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ): NodeDelegate {
        $class = get_class($this) . '\\FormDelegate';

        if (!class_exists($class)) {
            throw Exceptional::Setup('Unable to find form delegate for Nightfire Block: ' . $this->getName());
        }

        /** @var class-string<NodeDelegate> $class */
        return new $class($this, $context, $state, $event, $id);
    }
}
