<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\Element;

use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\Element;

use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\arch\node\form\SelectorDelegate;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<Element>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var Element
     */
    protected Block $block;

    protected function loadDelegates(): void
    {
        $this->loadDelegate('element', '~/content/elements/ElementSelector')
            ->as(SelectorDelegate::class)
            ->isForOne(true)
            ->isRequired($this->_isRequired);
    }

    protected function setDefaultValues(): void
    {
        $id = $this->data->content->element->select('id')
            ->where('slug', '=', $this->block->getSlug())
            ->toValue('id');

        $this['element']->as(SelectorDelegate::class)
            ->setSelected($id);
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $this['element']->as(SelectorDelegate::class)
            ->renderFieldContent($field);
    }

    public function apply(): Block
    {
        $id = $this['element']->as(SelectorDelegate::class)
            ->apply();

        $slug = $this->data->content->element->select('slug')
            ->where('id', '=', $id)
            ->toValue('slug');

        $this->block->setSlug($slug);
        return $this->block;
    }
}
