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

use DecodeLabs\Metamorph;

class Nightfire implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;


    // Block
    public function normalizeBlock($block)
    {
        if (empty($block)) {
            return null;
        }

        if (!$block instanceof fire\IBlock) {
            $block = fire\block\Base::fromXml($block);
        }

        return $block;
    }

    public function renderBlock($block)
    {
        if (!$block = $this->normalizeBlock($block)) {
            return null;
        }

        $output = $block->renderTo($this->getView());
        $test = trim($output);

        if (empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderBlockPreview($block, $length=null)
    {
        $output = $this->renderBlock($block);

        return Metamorph::htmlToText($output, [
            'maxLength' => $length,
            'wrap' => true
        ]);
    }



    // Slot
    public function normalizeSlot($slot)
    {
        if (empty($slot)) {
            return null;
        }

        if (!$slot instanceof fire\ISlotContent) {
            $slot = fire\slot\Content::fromXml($slot);
        }

        return $slot;
    }

    public function renderSlot($slot)
    {
        if (!$slot = $this->normalizeSlot($slot)) {
            return null;
        }

        $output = $slot->renderTo($this->getView());
        $test = trim($output);

        if (empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderSlotPreview($slot, $length=null)
    {
        $output = $this->renderSlot($slot);

        return Metamorph::htmlToText($output, [
            'maxLength' => $length,
            'wrap' => true
        ]);
    }

    public function newSlotDefinition($category=null): fire\ISlotDefinition
    {
        return (new fire\slot\Definition())
            ->setCategory($category);
    }


    // Layout
    public function renderLayoutPreview($layout)
    {
        if (empty($layout)) {
            return null;
        }

        if (!$layout instanceof fire\ILayoutContent) {
            $layout = fire\layout\Content::fromXml($layout);
        }

        return $this->renderSlot($layout->getSlot('primary'));
    }


    // Element
    public function renderElement($slug)
    {
        $body = $this->context->data->content->element->select('body')
            ->where('slug', '=', $slug)
            ->toValue('body');

        if (!$body) {
            if (!df\Launchpad::$app->isProduction()) {
                return $this->getView()->html->flashMessage('Dynamic element \''.$slug.'\' not found', 'error');
            }

            return;
        }

        return $this->renderSlot($body);
    }
}
