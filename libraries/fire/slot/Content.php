<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire\slot;

use DecodeLabs\Exceptional;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Serializable as XmlSerializable;

use DecodeLabs\Exemplar\SerializableTrait as XmlSerializableTrait;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\Error as ErrorBlock;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use df\aura;
use df\core;
use df\fire;

class Content implements fire\ISlotContent
{
    use core\collection\TAttributeContainer;
    use XmlSerializableTrait;
    use aura\view\TView_DeferredRenderable;
    use core\TStringProvider;

    public $blocks;
    protected $_isNested = false;
    protected $_hasChanged = false;

    public function __construct(string $id = null)
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


    /**
     * Set nested
     */
    public function setNested(bool $nested): static
    {
        $this->_isNested = $nested;

        foreach ($this->blocks as $block) {
            $block->setNested($this->_isNested);
        }

        return $this;
    }

    /**
     * Is nested
     */
    public function isNested(): bool
    {
        return $this->_isNested;
    }


    // Changes
    public function hasChanged(bool $flag = null)
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
            if (!$block = BlockAbstract::normalize($block)) {
                continue;
            }

            $this->addBlock($block);
        }

        return $this;
    }

    public function setBlock(int $index, Block $block)
    {
        if ($block !== $this->blocks->get($index)) {
            $this->_hasChanged = true;
        }

        $this->blocks->set($index, $block);
        return $this;
    }

    public function putBlock(int $index, Block $block)
    {
        $this->_hasChanged = true;
        $this->blocks->put($index, $block);
        return $this;
    }

    public function addBlock(Block $block)
    {
        $this->_hasChanged = true;
        $this->blocks->push($block);
        return $this;
    }

    public function getBlock(int $index): ?Block
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
            if ($element->getTagName() === 'block') {
                try {
                    $block = BlockAbstract::fromXml($element);
                } catch (Exceptional\NotFoundException $e) {
                    /** @var ErrorBlock $block */
                    $block = BlockAbstract::factory('Error');
                    $block->setError($e);
                    $block->setType($element['type']);
                    $block->setData($element->getFirstCDataSection());
                }

                $this->addBlock($block);
                return;
            }

            throw Exceptional::UnexpectedValue(
                'Slot content object expected slot xml element - found ' . $element->getTagName()
            );
        }

        $this->setAttributes($element->getAttributes());

        foreach ($element->block as $blockNode) {
            try {
                $block = BlockAbstract::fromXml($blockNode);
            } catch (Exceptional\NotFoundException $e) {
                /** @var ErrorBlock $block */
                $block = BlockAbstract::factory('Error');
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
