<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire\slot;

use df;
use df\core;
use df\fire;
use df\aura;
use df\arch;

class Definition implements fire\ISlotDefinition
{
    protected $_id;
    protected $_name;
    protected $_isStatic = false;
    protected $_minBlocks = 0;
    protected $_maxBlocks = null;
    protected $_category;



    // Interchange
    public static function fromArray(array $values): fire\ISlotDefinition
    {
        $output = new self(
            $values['id'] ?? null,
            $values['name'] ?? null,
            $values['static'] ?? false
        );

        $output->_minBlocks = $values['minBlocks'] ?? 0;
        $output->_maxBlocks = $values['maxBlocks'] ?? null;
        $output->_category = $values['category'] ?? null;

        return $output;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'static' => $this->_isStatic,
            'minBlocks' => $this->_minBlocks,
            'maxBlocks' => $this->_maxBlocks,
            'category' => $this->_category
        ];
    }

    public static function createDefault(): fire\ISlotDefinition
    {
        return new self('default', 'Default');
    }



    // Construct
    public function __construct(string $id=null, string $name=null, bool $isStatic=false)
    {
        $this->setId($id);
        $this->setName($name);
        $this->_isStatic = $isStatic;
    }


    // Id
    public function setId(?string $id)
    {
        if ($id === null) {
            $id = 'primary';
        }

        $this->_id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function isPrimary(): bool
    {
        return $this->_id == 'primary';
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
        if ($this->isPrimary()) {
            return true;
        }

        return $this->_isStatic;
    }


    // Blocks
    public function setMinBlocks(int $min)
    {
        $this->_minBlocks = $min;
        return $this;
    }

    public function getMinBlocks(): int
    {
        return $this->_minBlocks;
    }

    public function setMaxBlocks(?int $max)
    {
        if ($max <= 0) {
            $max = null;
        }

        $this->_maxBlocks = $max;
        return $this;
    }

    public function getMaxBlocks(): ?int
    {
        return $this->_maxBlocks;
    }

    public function hasBlockLimit(): bool
    {
        return $this->_maxBlocks !== null;
    }


    // Category
    public function setCategory($category)
    {
        $this->_category = fire\Category\Base::normalizeName($category);
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->_category;
    }
}
