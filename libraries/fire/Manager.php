<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire;

use df;
use df\core;
use df\fire;
    
class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://fire';

    protected $_categories = null;
    protected $_blocks = null;

    public function getConfig() {
        return Config::getInstance();
    }

    public function getCategories() {
        $this->_loadCategories();

        return $this->_categories;
    }

    public function getCategory($name) {
        $this->_loadCategories();

        if($name instanceof ICategory) {
            $name = $name->getName();
        }

        if(isset($this->_categories[$name])) {
            return $this->_categories[$name];
        }
    }

    protected function _loadCategories() {
        if($this->_categories !== null) {
            return;
        }

        $this->_loadBlocks();

        // TODO: cache result



        // Load the category list
        $this->_categories = [];
        $blockIndex = [];

        foreach(df\Launchpad::$loader->lookupClassList('fire/category') as $name => $class) {
            try {
                $category = fire\category\Base::factory($name);
            } catch(\Exception $e) {
                continue;
            }

            $this->_categories[$name] = $category;
            $blockIndex[$category->getName()] = [];

            foreach($category->getDefaultBlocks() as $blockName) {
                if(!isset($this->_blocks[$blockName])) {
                    continue;
                }

                $blockIndex[$category->getName()][$blockName] = $this->_blocks[$blockName];
            }
        }


        // Index the blocks into sets
        foreach($this->_blocks as $block) {
            foreach($block->getDefaultCategories() as $catName) {
                if(!isset($this->_categories[$catName])) {
                    continue;
                }

                $blockIndex[$catName][$block->getName()] = $block;
            }
        }


        // Update index with config data
        $config = $this->getConfig();

        foreach($config->getCategoryAugmentations() as $catName => $blockSet) {
            if(!isset($this->_categories[$catName])) {
                continue;
            }

            foreach($blockSet as $blockName => $enabled) {
                if(!isset($this->_blocks[$blockName])) {
                    continue;
                }

                if($enabled) {
                    $blockIndex[$catName][$blockName] = $this->_blocks[$blockName];
                } else {
                    unset($blockIndex[$catName][$blockName]);
                }
            }
        }


        // Add the block index to the categories
        foreach($blockIndex as $catName => $blockSet) {
            $category = $this->_categories[$catName];
            $outputTypes = $category->getRequiredOutputTypes();

            foreach($blockSet as $blockName => $block) {
                // Check block is compatible
                if(!empty($outputTypes)) {
                    $canOutput = false;

                    foreach($outputTypes as $type) {
                        if($block->canOutput($type)) {
                            $canOutput = true;
                            break;
                        }
                    }

                    if(!$canOutput) {
                        continue;
                    }
                }

                $category->addBlock($block);
            }
        }
    }



// Blocks
    public function isBlockAvailable($name) {
        $this->_loadBlocks();
        return isset($this->_blocks[ucfirst($name)]);
    }

    public function getAllBlocks() {
        $this->_loadBlocks();
        return $this->_blocks;
    }

    public function getAllBlockNames() {
        $output = [];

        foreach($this->getAllBlocks() as $block) {
            $output[$block->getName()] = $block->getDisplayName();
        }

        return $output;
    }

    public function getAllBlockNamesByFormat() {
        $output = [];

        foreach($this->getAllBlocks() as $block) {
            $output[$block->getFormat()][$block->getName()] = $block->getDisplayName();
        }

        $formatWeights = fire\category\Base::getFormatWeights();

        uksort($output, function($a, $b) use($formatWeights) {
            return @$formatWeights[$a] < @$formatWeights[$b];
        });

        /*
        foreach(array_keys($output) as $key) {
            asort($output[$key]);
        }
        */

        return $output;
    }

    public function getBlocksFor($outputType) {
        $this->_loadBlocks();
        $output = [];

        foreach($this->_blocks as $name => $block) {
            if($block->canOutput($outputType)) {
                $output[$name] = $block;
            }
        }

        return $output;
    }

    public function getCategoryBlocks($category, $outputType=null) {
        $this->_loadCategories();

        if($category instanceof ICategory) {
            $category = $category->getName();
        }

        if(!isset($this->_categories[$category])) {
            return [];
        }

        $this->_loadBlocks();
        $category = $this->_categories[$category];
        $output = [];

        foreach($category->getBlocks() as $blockName) {
            if(!isset($this->_blocks[$blockName])) {
                continue;
            }

            $block = $this->_blocks[$blockName];

            if($outputType !== null && !$block->canOutput($outputType)) {
                continue;
            }

            $output[$block->getName()] = $block;
        }        

        return $output;
    }

    public function getCategoryBlockNames($category, $outputType=null) {
        $output = [];

        foreach($this->getCategoryBlocks($category, $outputType) as $block) {
            $output[$block->getName()] = $block->getDisplayName();
        }

        //asort($output);

        return $output;
    }

    public function getCategoryBlockNamesByFormat($category, $outputType=null) {
        $output = [];

        foreach($this->getCategoryBlocks($category, $outputType) as $block) {
            $output[$block->getFormat()][$block->getName()] = $block->getDisplayName();
        }

        if($category = $this->getCategory($category)) {
            $formatWeights = $category->getFormatWeights();
        } else {
            $formatWeights = fire\category\Base::getFormatWeights();
        }

        uksort($output, function($a, $b) use($formatWeights) {
            return @$formatWeights[$a] < @$formatWeights[$b];
        });

        /*
        foreach(array_keys($output) as $key) {
            asort($output[$key]);
        }
        */

        return $output;
    }



    protected function _loadBlocks() {
        if($this->_blocks !== null) {
            return;
        }

        // TODO: cache result

        $this->_blocks = [];

        foreach(df\Launchpad::$loader->lookupClassList('fire/block') as $name => $class) {
            try {
                $block = fire\block\Base::factory($name);
            } catch(\Exception $e) {
                continue;
            }

            $this->_blocks[$name] = $block;
        }
    }
}