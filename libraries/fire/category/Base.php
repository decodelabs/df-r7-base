<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\category;

use df;
use df\core;
use df\fire;
use df\aura;

use DecodeLabs\Exceptional;

abstract class Base implements fire\ICategory
{
    use core\TStringProvider;

    const DEFAULT_BLOCKS = ['RawHtml'];
    const DEFAULT_EDITOR_BLOCK = null;

    const FORMAT_WEIGHTS = [
        'markup' => 100,
        'text' => 90,
        'structure' => 75,
        'image' => 50,
        'video' => 25,
        'audio' => 10
    ];

    protected $_blocks = [];

    public static function factory(string $name): fire\ICategory
    {
        $name = ucfirst($name);
        $class = 'df\\fire\\category\\'.$name;

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Content category '.$name.' could not be found'
            );
        }

        return new $class();
    }

    public static function normalize($category): ?fire\ICategory
    {
        if ($category instanceof fire\ICategory || $category === null) {
            return $category;
        }

        return self::factory($category);
    }

    public static function normalizeName($category): ?string
    {
        if ($category = self::normalize($category)) {
            return $category->getName();
        } else {
            return null;
        }
    }


    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function toString(): string
    {
        return $this->getName();
    }

    public static function getDefaultBlockTypes(): array
    {
        return (array)static::DEFAULT_BLOCKS;
    }

    public function getDefaultEditorBlockType(): ?string
    {
        if (!empty(static::DEFAULT_EDITOR_BLOCK)) {
            return static::DEFAULT_EDITOR_BLOCK;
        }

        $types = $this->getDefaultBlockTypes();
        $output = array_shift($types);

        if (!empty($output)) {
            return $output;
        }

        return null;
    }

    public function setBlocks(array $blocks)
    {
        $this->_blocks = [];
        return $this->addBlocks($blocks);
    }

    public function addBlocks(array $blocks)
    {
        foreach ($blocks as $block) {
            $this->addBlock($block);
        }

        return $this;
    }

    public function addBlock($block)
    {
        if ($block = fire\block\Base::normalize($block)) {
            $this->_blocks[$block->getName()] = true;
        }

        return $this;
    }

    public function hasBlock(string $block): bool
    {
        return isset($this->_blocks[ucfirst($block)]);
    }

    public function getBlocks(): array
    {
        return array_keys($this->_blocks);
    }

    public function removeBlock(string $block)
    {
        unset($this->_blocks[ucfirst($block)]);
        return $this;
    }

    public static function getFormatWeights(): array
    {
        return (array)static::FORMAT_WEIGHTS;
    }
}
