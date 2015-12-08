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

class Nightfire implements arch\IDirectoryHelper, aura\view\IImplicitViewHelper {

    use arch\TDirectoryHelper;
    use aura\view\TViewAwareDirectoryHelper;

    public function renderBlock($block, $category=null, $isNested=null) {
        if(empty($block)) {
            return null;
        }

        if(!$block instanceof fire\block\IBlock) {
            $block = fire\block\Base::fromXml($block);
        }

        if($isNested !== null) {
            $block->isNested((bool)$isNested);
        }

        if($category = $this->_loadCategory($category)) {
            $output = $category->renderBlock($block, $this->view);
        } else {
            $output = $block->renderTo($this->view);
        }

        $test = trim($output);

        if(empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderBlockPreview($block, $category=null, $length=null) {
        $output = $this->renderBlock($block, $category);
        return $this->view->html->previewText($output, $length);
    }

    public function renderSlot($slot, $category=null, $isNested=null) {
        if(empty($slot)) {
            return null;
        }

        if(!$slot instanceof fire\slot\IContent) {
            $slot = fire\slot\Content::fromXml($slot);
        }

        if($isNested === null) {
            $isNested = $slot->isNested();
        }

        $slot->isNested((bool)$isNested);

        if($category = $this->_loadCategory($category)) {
            $output = $category->renderSlot($slot, $this->view);
        } else {
            $output = $slot->renderTo($this->view);
        }

        $test = trim($output);

        if(empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderSlotPreview($slot, $category=null, $length=null) {
        $output = $this->renderSlot($slot, $category);
        return $this->view->html->previewText($output, $length);
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

    public function renderLayoutPreview($layout) {
        if(empty($layout)) {
            return null;
        }

        if(!$layout instanceof fire\layout\IContent) {
            $layout = fire\layout\Content::fromXml($layout);
        }

        return $this->renderSlot($layout->getSlot('primary'));
    }

    public function renderElement($slug) {
        $body = $this->context->data->content->element->select('body')
            ->where('slug', '=', $slug)
            ->toValue('body');

        if(!$body) {
            if(!df\Launchpad::$application->isProduction()) {
                return $this->view->html->flashMessage('Dynamic element \''.$slug.'\' not found', 'error');
            }

            return;
        }

        return $this->renderSlot($body);
    }
}