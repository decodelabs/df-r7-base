<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire\layout;

use DecodeLabs\Glitch\Dumpable;

use df\fire;

class Definition implements fire\ILayoutDefinition, Dumpable
{
    protected $_id;
    protected $_name;
    protected $_isStatic = false;
    protected $_areas = [];
    protected $_slots = [];

    public function __construct(string $id = null, string $name = null, bool $isStatic = false)
    {
        $this->setId($id);
        $this->setName($name);
        $this->_isStatic = $isStatic;
    }


    // Id
    public function setId(?string $id)
    {
        if ($id === null) {
            $id = 'Default';
        }

        $this->_id = ucfirst($id);
        return $this;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    // Name
    public function setName(?string $name)
    {
        if ($name === null) {
            $name = $this->_id;
        }

        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->_name;
    }


    // Static
    public function isStatic(): bool
    {
        return $this->_isStatic;
    }


    // Areas
    public function setAreas(array $areas)
    {
        $this->_areas = $areas;

        foreach ($this->_areas as $i => $area) {
            $this->_areas[$i] = ltrim((string)$area, '~');
        }

        return $this;
    }

    public function getAreas()
    {
        return $this->_areas;
    }

    public function hasArea(string $area): bool
    {
        return in_array(ltrim($area, '~'), $this->_areas);
    }

    public function hasAreas(): bool
    {
        return !empty($this->_areas);
    }

    public function countAreas(): int
    {
        return count($this->_areas);
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
            if ($slot instanceof fire\ISlotDefinition) {
                $this->addSlot($slot);
            }
        }

        return $this;
    }

    public function addSlot(fire\ISlotDefinition $slot)
    {
        $this->_slots[$slot->getId()] = $slot;
        return $this;
    }

    public function getSlots(): array
    {
        return $this->_slots;
    }

    public function getSlot(string $id): ?fire\ISlotDefinition
    {
        if (!isset($this->_slots[$id])) {
            return null;
        }

        return $this->_slots[$id];
    }

    public function removeSlot(string $id)
    {
        unset($this->_slots[$id]);
        return $this;
    }

    public function countSlots(): int
    {
        return count($this->_slots);
    }

    public function setSlotOrder(array $ids)
    {
        $list = [];

        foreach ($ids as $id) {
            if (isset($this->_slots[$id])) {
                $list[$id] = $this->_slots[$id];
                unset($this->_slots[$id]);
            }
        }

        foreach ($this->_slots as $id => $slot) {
            $list[$id] = $slot;
        }

        $this->_slots = $list;
        return $this;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*id' => $this->_id,
            '*name' => $this->_name
        ];

        yield 'values' => $this->_slots;

        if ($this->_isStatic) {
            yield 'property:*static' => $this->_isStatic;
        }

        if (!empty($this->_areas)) {
            yield 'property:*areas' => $this->_areas;
        }
    }
}
