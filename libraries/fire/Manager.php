<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire;

use DecodeLabs\Archetype;
use DecodeLabs\R7\Config\Nightfire as NightfireConfig;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\Category;
use DecodeLabs\R7\Nightfire\CategoryAbstract;
use df\core;

class Manager implements IManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://fire';

    protected $_categories = null;
    protected $_blocks = null;

    public function getConfig(): NightfireConfig
    {
        return NightfireConfig::load();
    }

    public function getCategories(): array
    {
        $this->_loadCategories();
        return $this->_categories;
    }

    public function getCategory(?string $name): ?Category
    {
        if ($name === null) {
            return null;
        }

        $this->_loadCategories();

        if (!isset($this->_categories[$name])) {
            return null;
        }

        return $this->_categories[$name];
    }

    protected function _loadCategories(): void
    {
        if ($this->_categories !== null) {
            return;
        }

        $this->_loadBlocks();

        // TODO: cache result


        // Load the category list
        $this->_categories = [];
        $blockIndex = [];

        foreach (Archetype::scanClasses(Category::class) as $path => $class) {
            $parts = explode('\\', $class);
            $name = array_pop($parts);

            try {
                $category = CategoryAbstract::factory($name);
            } catch (\Throwable $e) {
                continue;
            }

            $this->_categories[$name] = $category;
            $blockIndex[$category->getName()] = [];

            foreach ($category->getDefaultBlockTypes() as $blockName) {
                if (!isset($this->_blocks[$blockName])) {
                    continue;
                }

                $blockIndex[$category->getName()][$blockName] = $this->_blocks[$blockName];
            }
        }


        // Index the blocks into sets
        foreach ($this->_blocks as $block) {
            foreach ($block->getDefaultCategories() as $catName) {
                if (!isset($this->_categories[$catName])) {
                    continue;
                }

                $blockIndex[$catName][$block->getName()] = $block;
            }
        }


        // Update index with config data
        $config = $this->getConfig();

        foreach ($config->getCategoryAugmentations() as $catName => $blockSet) {
            if (!isset($this->_categories[$catName])) {
                continue;
            }

            foreach ($blockSet as $blockName => $enabled) {
                if (!isset($this->_blocks[$blockName])) {
                    continue;
                }

                if ($enabled) {
                    $blockIndex[$catName][$blockName] = $this->_blocks[$blockName];
                } else {
                    unset($blockIndex[$catName][$blockName]);
                }
            }
        }


        // Add the block index to the categories
        foreach ($blockIndex as $catName => $blockSet) {
            $category = $this->_categories[$catName];

            foreach ($blockSet as $blockName => $block) {
                $category->addBlock($block);
            }
        }
    }



    // Blocks
    public function isBlockAvailable(string $name): bool
    {
        $this->_loadBlocks();
        return isset($this->_blocks[ucfirst($name)]);
    }

    public function getAllBlocks(): array
    {
        $this->_loadBlocks();
        return $this->_blocks;
    }

    public function getAllBlockNames(): array
    {
        $output = [];

        foreach ($this->getAllBlocks() as $block) {
            if ($block->isHidden()) {
                continue;
            }

            $output[$block->getName()] = $block->getDisplayName();
        }

        return $output;
    }

    public function getAllBlockNamesByFormat(): array
    {
        $output = [];

        foreach ($this->getAllBlocks() as $block) {
            if ($block->isHidden()) {
                continue;
            }

            $output[$block->getFormat()][$block->getName()] = $block->getDisplayName();
        }

        $formatWeights = CategoryAbstract::getFormatWeights();

        uksort($output, function ($a, $b) use ($formatWeights) {
            return ($formatWeights[$a] ?? 0) <=> ($formatWeights[$b] ?? 0);
        });

        return $output;
    }

    public function getCategoryBlocks(string $category): array
    {
        $this->_loadCategories();

        if (!isset($this->_categories[$category])) {
            return [];
        }

        $this->_loadBlocks();
        $category = $this->_categories[$category];
        $output = [];

        foreach ($category->getBlocks() as $blockName) {
            if (!isset($this->_blocks[$blockName])) {
                continue;
            }

            $block = $this->_blocks[$blockName];
            $output[$block->getName()] = $block;
        }

        return $output;
    }

    public function getCategoryBlockNames(string $category): array
    {
        $output = [];

        foreach ($this->getCategoryBlocks($category) as $block) {
            if ($block->isHidden()) {
                continue;
            }

            $output[$block->getName()] = $block->getDisplayName();
        }

        return $output;
    }

    public function getCategoryBlockNamesByFormat(string $category): array
    {
        $output = [];

        foreach ($this->getCategoryBlocks($category) as $block) {
            if ($block->isHidden()) {
                continue;
            }

            $output[$block->getFormat()][$block->getName()] = $block->getDisplayName();
        }

        if ($category = $this->getCategory($category)) {
            $formatWeights = $category->getFormatWeights();
        } else {
            $formatWeights = CategoryAbstract::getFormatWeights();
        }

        uksort($output, function ($a, $b) use ($formatWeights) {
            return ($formatWeights[$b] ?? 0) <=> ($formatWeights[$a] ?? 0);
        });

        return $output;
    }



    protected function _loadBlocks()
    {
        if ($this->_blocks !== null) {
            return;
        }

        // TODO: cache result

        $this->_blocks = [];

        foreach (Archetype::scanClasses(Block::class) as $path => $class) {
            $parts = explode('\\', $class);
            $name = array_pop($parts);

            try {
                $block = BlockAbstract::factory($name);
            } catch (\Throwable $e) {
                continue;
            }

            $this->_blocks[$name] = $block;
        }
    }
}
