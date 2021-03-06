<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\slot;

use df;
use df\core;
use df\fire;
use df\flex;
use df\arch;
use df\aura;

use DecodeLabs\Tagged\Xml\Element as XmlElement;
use DecodeLabs\Tagged\Xml\Writer as XmlWriter;
use DecodeLabs\Tagged\Xml\Serializable as XmlSerializable;
use DecodeLabs\Tagged\Xml\SerializableTrait as XmlSerializableTrait;

use DecodeLabs\Exceptional;

class Content implements fire\ISlotContent
{
    use core\collection\TAttributeContainer;
    use XmlSerializableTrait;
    use aura\view\TView_DeferredRenderable;
    use core\TStringProvider;

    public $blocks;
    protected $_isNested = false;
    protected $_hasChanged = false;

    public function __construct(string $id=null)
    {
        $this->blocks = new core\collection\Queue();

        if ($id !== null) {
            $this->setId($id);
        }
    }

    public function __clone()
    {
        $this->blocks = clone $this->blocks;
    }

    // Id
    public function setId(?string $id)
    {
        return $this->setAttribute('id', $id);
    }

    public function getId(): ?string
    {
        return $this->getAttribute('id');
    }

    public function isPrimary(): bool
    {
        return $this->getAttribute('id') == 'primary';
    }

    // Nesting
    public function isNested(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isNested = $flag;

            foreach ($this->blocks as $block) {
                $block->isNested($this->_isNested);
            }

            return $this;
        }

        return $this->_isNested;
    }

    // Changes
    public function hasChanged(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_hasChanged = $flag;
            return $this;
        }

        return $this->_hasChanged;
    }

    // Blocks
    public function setBlocks(array $blocks)
    {
        return $this->clearBlocks()->addBlocks($blocks);
    }

    public function addBlocks(array $blocks)
    {
        foreach ($blocks as $block) {
            if (!$block = fire\block\Base::normalize($block)) {
                continue;
            }

            $this->addBlock($block);
        }

        return $this;
    }

    public function setBlock(int $index, fire\IBlock $block)
    {
        if ($block !== $this->blocks->get($index)) {
            $this->_hasChanged = true;
        }

        $this->blocks->set($index, $block);
        return $this;
    }

    public function putBlock(int $index, fire\IBlock $block)
    {
        $this->_hasChanged = true;
        $this->blocks->put($index, $block);
        return $this;
    }

    public function addBlock(fire\IBlock $block)
    {
        $this->_hasChanged = true;
        $this->blocks->push($block);
        return $this;
    }

    public function getBlock(int $index): ?fire\IBlock
    {
        return $this->blocks->get($index);
    }

    public function getBlocks(): array
    {
        return $this->blocks->toArray();
    }

    public function hasBlock(int $index): bool
    {
        return $this->blocks->has($index);
    }

    public function removeBlock(int $index)
    {
        $this->_hasChanged = true;
        $this->blocks->remove($index);

        return $this;
    }

    public function clearBlocks()
    {
        $this->_hasChanged = true;
        $this->blocks->clear();
        return $this;
    }

    public function countBlocks(): int
    {
        return $this->blocks->count();
    }

    // Rendering
    public function toString(): string
    {
        return (string)$this->render();
    }

    public function render()
    {
        $output = [];
        $renderTarget = $this->getRenderTarget();

        foreach ($this->blocks as $block) {
            $output[] = $block->renderTo($renderTarget);
        }

        return new aura\html\ElementContent($output, $this);
    }

    public function xmlUnserialize(XmlElement $element): void
    {
        $this->blocks = new core\collection\Queue();

        if ($element->getTagName() != 'slot') {
            throw Exceptional::UnexpectedValue(
                'Slot content object expected slot xml element - found '.$element->getTagName()
            );
        }

        $this->setAttributes($element->getAttributes());

        foreach ($element->block as $blockNode) {
            try {
                $block = fire\block\Base::fromXml($blockNode);
            } catch (fire\block\NotFoundException $e) {
                $block = new fire\block\Error();
                $block->setError($e);
                $block->setType($blockNode['type']);
                $block->setData($blockNode->getFirstCDataSection());
            }

            $this->addBlock($block);
        }
    }

    public function xmlSerialize(XmlWriter $writer): void
    {
        $writer->writeElement('slot', function ($writer) {
            foreach ($this->blocks as $block) {
                if ($block instanceof XmlSerializable) {
                    $block->xmlSerialize($writer);
                } else {
                    $writer->importXmlElement($block->toXmlElement());
                }
            }
        }, $this->_attributes);
    }
}
