<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\layout;

use DecodeLabs\Exceptional;
use DecodeLabs\Exemplar\Element as XmlElement;

use DecodeLabs\Exemplar\SerializableTrait as XmlSerializableTrait;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use df\core;

use df\fire;

class Content implements fire\ILayoutContent
{
    use core\collection\TAttributeContainer;
    use XmlSerializableTrait;

    protected $_slots = [];

    public function __construct(string $id = null)
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
                throw Exceptional::InvalidArgument(
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

    public function xmlUnserialize(XmlElement $element): void
    {
        if ($element->getTagName() != 'layout') {
            throw Exceptional::UnexpectedValue(
                'Layout content object expected layout xml element'
            );
        }

        $this->setId($element->getAttribute('id'));

        foreach ($element->slot as $slotNode) {
            $slot = fire\slot\Content::fromXmlElement($slotNode);
            $this->setSlot($slot);
        }
    }

    public function xmlSerialize(XmlWriter $writer): void
    {
        $writer->writeElement('layout', function ($writer) {
            foreach ($this->_slots as $slot) {
                $slot->xmlSerialize($writer);
            }
        }, $this->_attributes);
    }
}
