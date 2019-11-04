<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use df;
use df\core;
use df\fire;
use df\flex;
use df\arch;
use df\aura;

use DecodeLabs\Glitch;

class Content implements fire\ILayoutContent
{
    use core\collection\TAttributeContainer;
    use flex\xml\TInterchange;

    protected $_slots = [];

    public function __construct(string $id=null)
    {
        if ($id !== null) {
            $this->setId($id);
        }
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

    // Slots
    public function setSlots(array $slots)
    {
        $this->_slots = [];
        return $this->addSlots($slots);
    }

    public function addSlots(array $slots)
    {
        foreach ($slots as $slot) {
            if (empty($slot)) {
                continue;
            }

            if (!$slot instanceof fire\ISlotContent) {
                throw Glitch::EInvalidArgument(
                    'Invalid slot content detected'
                );
            }

            $this->setSlot($slot);
        }

        return $this;
    }

    public function setSlot(fire\ISlotContent $slot)
    {
        $this->_slots[$slot->getId()] = $slot;
        return $this;
    }

    public function getSlot(string $id): ?fire\ISlotContent
    {
        if (!isset($this->_slots[$id])) {
            return null;
        }

        return $this->_slots[$id];
    }

    public function getSlots(): array
    {
        return $this->_slots;
    }

    public function hasSlot(string ...$ids): bool
    {
        foreach ($ids as $id) {
            if (isset($this->_slots[$id])) {
                return true;
            }
        }

        return false;
    }

    public function removeSlot(string $id)
    {
        unset($this->_slots[$id]);
        return $this;
    }

    public function clearSlots()
    {
        $this->_slots = [];
        return $this;
    }

    public function countSlots(): int
    {
        return count($this->_slots);
    }

    // XML interchange
    public function readXml(flex\xml\ITree $reader)
    {
        if ($reader->getTagName() != 'layout') {
            throw Glitch::EUnexpectedValue(
                'Layout content object expected layout xml element'
            );
        }

        $this->setId($reader->getAttribute('id'));

        foreach ($reader->slot as $slotNode) {
            $slot = new fire\slot\Content();
            $slot->readXml($slotNode);
            $this->setSlot($slot);
        }

        return $this;
    }

    public function writeXml(flex\xml\IWriter $writer)
    {
        $writer->startElement('layout');
        $writer->setAttributes($this->_attributes);

        foreach ($this->_slots as $slot) {
            $slot->writeXml($writer);
        }

        $writer->endElement();
        return $this;
    }
}
