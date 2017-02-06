<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
use df\aura;
use df\fire;

class Nightfire implements arch\IDirectoryHelper {

    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;


// Block
    public function normalizeBlock($block) {
        if(empty($block)) {
            return null;
        }

        if(!$block instanceof fire\block\IBlock) {
            $block = fire\block\Base::fromXml($block);
        }

        return $block;
    }

    public function renderBlock($block, $category=null, $isNested=null) {
        if(!$block = $this->normalizeBlock($block)) {
            return null;
        }

        if($isNested !== null) {
            $block->isNested((bool)$isNested);
        }

        if($category = $this->_loadCategory($category)) {
            $output = $category->renderBlock($block, $this->getView());
        } else {
            $output = $block->renderTo($this->getView());
        }

        $test = trim($output);

        if(empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderBlockPreview($block, $category=null, $length=null) {
        $output = $this->renderBlock($block, $category);
        return $this->getView()->html->previewText($output, $length);
    }



// Slot
    public function normalizeSlot($slot) {
        if(empty($slot)) {
            return null;
        }

        if(!$slot instanceof fire\slot\IContent) {
            $slot = fire\slot\Content::fromXml($slot);
        }

        return $slot;
    }

    public function renderSlot($slot, $category=null, $isNested=null) {
        if(!$slot = $this->normalizeSlot($slot)) {
            return null;
        }

        if($isNested === null) {
            $isNested = $slot->isNested();
        }

        $slot->isNested((bool)$isNested);

        if($category = $this->_loadCategory($category)) {
            $output = $category->renderSlot($slot, $this->getView());
        } else {
            $output = $slot->renderTo($this->getView());
        }

        $test = trim($output);

        if(empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderSlotPreview($slot, $category=null, $length=null) {
        $output = $this->renderSlot($slot, $category);
        return $this->getView()->html->previewText($output, $length);
    }

    protected function _loadCategory($category) {
        if($category) {
            try {
                $category = fire\category\Base::factory($category);
            } catch(fire\RuntimeException $e) {
                $category = null;
            }
        }

        return $category;
    }


    public function newSlotDefinition($category=null): fire\slot\IDefinition {
        return (new fire\slot\Definition())
            ->setCategory($category);
    }


// Layout
    public function renderLayoutPreview($layout) {
        if(empty($layout)) {
            return null;
        }

        if(!$layout instanceof fire\layout\IContent) {
            $layout = fire\layout\Content::fromXml($layout);
        }

        return $this->renderSlot($layout->getSlot('primary'));
    }


// Element
    public function renderElement($slug) {
        $body = $this->context->data->content->element->select('body')
            ->where('slug', '=', $slug)
            ->toValue('body');

        if(!$body) {
            if(!df\Launchpad::$application->isProduction()) {
                return $this->getView()->html->flashMessage('Dynamic element \''.$slug.'\' not found', 'error');
            }

            return;
        }

        return $this->renderSlot($body);
    }
}