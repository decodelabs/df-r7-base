<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use DecodeLabs\Archetype;

use df\core;

abstract class CategoryAbstract implements Category
{
    use core\TStringProvider;

    public const DEFAULT_BLOCKS = ['RawHtml'];
    public const DEFAULT_EDITOR_BLOCK = null;

    public const FORMAT_WEIGHTS = [
        'markup' => 100,
        'text' => 90,
        'structure' => 75,
        'image' => 50,
        'video' => 25,
        'audio' => 10
    ];

    /**
     * @var array<string, bool>
     */
    protected array $_blocks = [];

    public static function factory(string $name): Category
    {
        $class = Archetype::resolve(Category::class, ucfirst($name));
        return new $class();
    }

    public static function normalize(
        string|Category|null $category
    ): ?Category {
        if (
            $category instanceof Category ||
            $category === null
        ) {
            return $category;
        }

        return static::factory($category);
    }

    public static function normalizeName(
        string|Category|null $category
    ): ?string {
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

    /**
     * @return array<string>
     */
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

    public function setBlocks(array $blocks): static
    {
        $this->_blocks = [];
        return $this->addBlocks($blocks);
    }

    public function addBlocks(array $blocks): static
    {
        foreach ($blocks as $block) {
            $this->addBlock($block);
        }

        return $this;
    }

    public function addBlock(
        string|Block $block
    ): static {
        if ($block = BlockAbstract::normalize($block)) {
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

    public function removeBlock(string $block): static
    {
        unset($this->_blocks[ucfirst($block)]);
        return $this;
    }

    public static function getFormatWeights(): array
    {
        return (array)static::FORMAT_WEIGHTS;
    }
}
