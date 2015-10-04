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
    
abstract class Base implements fire\ICategory {

    protected static $_requiredOutputTypes = ['html'];
    protected static $_defaultBlocks = ['RawHtml'];
    protected static $_formatWeights = [
        'markup' => 100,
        'text' => 90,
        'structure' => 75,
        'image' => 50,
        'video' => 25,
        'audio' => 10
    ];

    protected $_blocks = [];

    public static function factory($name) {
        if($name instanceof fire\ICategory) {
            return $name;
        }

        $name = ucfirst($name);
        $class = 'df\\fire\\category\\'.$name;

        if(!class_exists($class)) {
            throw new fire\RuntimeException(
                'Content category '.$name.' could not be found'
            );
        }

        return new $class();
    }


    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public static function getRequiredOutputTypes() {
        return (array)static::$_requiredOutputTypes;
    }

    public static function getDefaultBlocks() {
        return (array)static::$_defaultBlocks;
    }

    public function getDefaultEditorBlockType() {
        $types = $this->getDefaultBlocks();
        $output = array_shift($types);

        if(!empty($output)) {
            return $output;
        }

        return null;
    }

    public function setBlocks(array $blocks) {
        $this->_blocks = [];
        return $this->addBlocks($blocks);
    }

    public function addBlocks(array $blocks) {
        foreach($blocks as $block) {
            $this->addBlock($block);
        }

        return $this;
    }

    public function addBlock($block) {
        $block = fire\block\Base::factory($block);
        $this->_blocks[$block->getName()] = true;

        return $this;
    }

    public function hasBlock($block) {
        $block = fire\block\Base::factory($block);
        return isset($this->_blocks[$block->getName()]);
    }

    public function getBlocks() {
        return array_keys($this->_blocks);
    }

    public function removeBlock($block) {
        $block = fire\block\Base::factory($block);
        unset($this->_blocks[$block->getName()]);

        return $this;
    }

    public static function getFormatWeights() {
        return static::$_formatWeights;
    }

    public function renderBlock(fire\block\IBlock $block, aura\view\IView $view) {
        $name = $block->getName();
        $func = 'render'.$name.'Block';

        if(method_exists($this, $func)) {
            return $this->{$func}($block, $view);
        }

        return $block->renderTo($view);
    }

    public function renderSlot(fire\slot\IContent $slot, aura\view\IView $view) {
        $output = [];

        foreach($slot->getBlocks() as $block) {
            $output[] = $this->renderBlock($block, $view);
        }

        return new aura\html\ElementContent($output);
    }
}