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

class Content implements IContent {

    use core\collection\TAttributeContainer;
    use flex\xml\TReaderInterchange;
    use flex\xml\TWriterInterchange;
    use aura\view\TView_DeferredRenderable;
    use core\TStringProvider;

    public $blocks;
    protected $_isNested = false;
    protected $_hasChanged = false;

    public function __construct($id=null) {
        $this->blocks = new core\collection\Queue();

        if($id !== null) {
            $this->setId($id);
        }
    }

    public function __clone() {
        $this->blocks = clone $this->blocks;
    }

// Id
    public function setId($id) {
        return $this->setAttribute('id', $id);
    }

    public function getId() {
        return $this->getAttribute('id');
    }

    public function isPrimary() {
        return $this->getAttribute('id') == 'primary';
    }

// Nesting
    public function isNested(bool $flag=null) {
        if($flag !== null) {
            $this->_isNested = $flag;

            foreach($this->blocks as $block) {
                $block->isNested($this->_isNested);
            }

            return $this;
        }

        return $this->_isNested;
    }

// Changes
    public function hasChanged(bool $flag=null) {
        if($flag !== null) {
            $this->_hasChanged = $flag;
            return $this;
        }

        return $this->_hasChanged;
    }

// Blocks
    public function setBlocks(array $blocks) {
        return $this->clearBlocks()->addBlocks($blocks);
    }

    public function addBlocks(array $blocks) {
        foreach($blocks as $block) {
            if(!$block instanceof fire\block\IBlock) {
                throw new InvalidArgumentException(
                    'Invalid block content detected'
                );
            }

            $this->addBlock($block);
        }

        return $this;
    }

    public function setBlock($index, fire\block\IBlock $block) {
        if($block !== $this->blocks->get($index)) {
            $this->_hasChanged = true;
        }

        $this->blocks->set($index, $block);
        return $this;
    }

    public function putBlock($index, fire\block\IBlock $block) {
        $this->_hasChanged = true;
        $this->blocks->put($index, $block);
        return $this;
    }

    public function addBlock(fire\block\IBlock $block) {
        $this->_hasChanged = true;
        $this->blocks->push($block);
        return $this;
    }

    public function getBlock($index) {
        return $this->blocks->get($index);
    }

    public function getBlocks() {
        return $this->blocks->toArray();
    }

    public function hasBlock($index) {
        return $this->blocks->has($index);
    }

    public function removeBlock($index) {
        if($index instanceof fire\block\IBlock) {
            foreach($this->blocks as $i => $block) {
                if($index === $block) {
                    $this->_hasChanged = true;
                    $this->blocks->remove($i);
                    break;
                }
            }
        } else {
            $this->_hasChanged = true;
            $this->blocks->remove($index);
        }

        return $this;
    }

    public function clearBlocks() {
        $this->_hasChanged = true;
        $this->blocks->clear();
        return $this;
    }

    public function countBlocks() {
        return $this->blocks->count();
    }

// Rendering
    public function toString() {
        return $this->render();
    }

    public function render() {
        $output = [];
        $renderTarget = $this->getRenderTarget();

        foreach($this->blocks as $block) {
            $output[] = $block->renderTo($renderTarget);
        }

        return new aura\html\ElementContent($output, $this);
    }

// XML interchange
    public function readXml(flex\xml\IReadable $reader) {
        if($reader->getTagName() != 'slot') {
            throw new UnexpectedValueException(
                'Slot content object expected slot xml element - found '.$reader->getTagName()
            );
        }

        $this->setAttributes($reader->getAttributes());

        foreach($reader->block as $blockNode) {
            try {
                $block = fire\block\Base::fromXmlElement($blockNode);
            } catch(fire\block\IException $e) {
                $block = new fire\block\Error();
                $block->setError($e);
                $block->setType($blockNode['type']);
                $block->setData($blockNode->getFirstCDataSection());
            }

            $this->addBlock($block);
        }

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $writer->startElement('slot');
        $writer->setAttributes($this->_attributes);

        foreach($this->blocks as $block) {
            $block->writeXml($writer);
        }

        $writer->endElement();
        return $this;
    }
}