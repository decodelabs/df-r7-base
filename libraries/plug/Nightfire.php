<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Genesis;
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\R7\Nightfire\BlockAbstract;
use df\arch;
use df\aura;
use df\fire;

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

        if (!$block instanceof Block) {
            $block = BlockAbstract::fromXml($block);
        }

        return $block;
    }

    public function renderBlock($block)
    {
        if (!$block = $this->normalizeBlock($block)) {
            return null;
        }

        $output = $block->renderTo($this->getView());
        $test = trim((string)$output);

        if (empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderBlockPreview($block, $length = null)
    {
        $output = $this->renderBlock($block);

        return Metamorph::htmlToText($output, [
            'maxLength' => $length
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

        $output = (string)$slot->renderTo($this->getView());
        $test = trim($output);

        if (empty($test)) {
            return null;
        }

        return $output;
    }

    public function renderSlotPreview($slot, $length = null)
    {
        $output = $this->renderSlot($slot);

        return Metamorph::htmlToText($output, [
            'maxLength' => $length
        ]);
    }

    public function newSlotDefinition($category = null): fire\ISlotDefinition
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
            if (!Genesis::$environment->isProduction()) {
                return $this->getView()->html->flashMessage('Dynamic element \'' . $slug . '\' not found', 'error');
            }

            return;
        }

        return $this->renderSlot($body);
    }
}
