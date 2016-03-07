<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use df;
use df\core;
use df\fire;
use df\arch;
use df\aura;

class Content implements IContent {

    use core\collection\TAttributeContainer;
    use core\xml\TReaderInterchange;
    use core\xml\TWriterInterchange;

    protected $_slots = [];

    public function __construct($id=null) {
        if($id !== null) {
            $this->setId($id);
        }
    }

// Id
    public function setId($id) {
        return $this->setAttribute('id', $id);
    }

    public function getId() {
        return $this->getAttribute('id');
    }

// Slots
    public function setSlots(array $slots) {
        $this->_slots = [];
        return $this->addSlots($slots);
    }

    public function addSlots(array $slots) {
        foreach($slots as $slot) {
            if(empty($slot)) {
                continue;
            }

            if(!$slot instanceof fire\slot\IContent) {
                throw new InvalidArgumentException(
                    'Invalid slot content detected'
                );
            }

            $this->setSlot($slot);
        }

        return $this;
    }

    public function setSlot(fire\slot\IContent $slot) {
        $this->_slots[$slot->getId()] = $slot;
        return $this;
    }

    public function getSlot(string $id) {
        if(isset($this->_slots[$id])) {
            return $this->_slots[$id];
        }
    }

    public function getSlots() {
        return $this->_slots;
    }

    public function hasSlot(string $id) {
        return isset($this->_slots[$id]);
    }

    public function removeSlot(string $id) {
        unset($this->_slots[$id]);
        return $this;
    }

    public function clearSlots() {
        $this->_slots = [];
        return $this;
    }

    public function countSlots() {
        return count($this->_slots);
    }

// XML interchange
    public function readXml(core\xml\IReadable $reader) {
        if($reader->getTagName() != 'layout') {
            throw new UnexpectedValueException(
                'Layout content object expected layout xml element'
            );
        }

        $this->setId($reader->getAttribute('id'));

        foreach($reader->slot as $slotNode) {
            $slot = new fire\slot\Content();
            $slot->readXml($slotNode);
            $this->setSlot($slot);
        }

        return $this;
    }

    public function writeXml(core\xml\IWritable $writer) {
        $writer->startElement('layout');
        $writer->setAttributes($this->_attributes);

        foreach($this->_slots as $slot) {
            $slot->writeXml($writer);
        }

        $writer->endElement();
        return $this;
    }
}