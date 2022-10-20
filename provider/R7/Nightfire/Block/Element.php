<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use df\arch;
use df\aura;

use df\arch\IContext as Context;
use df\arch\node\IDelegate as NodeDelegate;
use df\arch\node\IFormState as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\scaffold\Node\Form\SelectorDelegate;
use df\aura\html\widget\Field as FieldWidget;

use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use DecodeLabs\Tagged\Markup;

class Element extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = [];

    protected ?string $slug = null;

    public function getFormat(): string
    {
        return 'structure';
    }


    /**
     * @return $this
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }


    public function isEmpty(): bool
    {
        return !strlen((string)$this->slug);
    }



    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->slug = $element['slug'];
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['slug'] = $this->slug;
    }


    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();
        return $view->nightfire->renderElement($this->slug);
    }


    // Form
    public function loadFormDelegate(
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ): NodeDelegate {
        /**
         * @extends BlockDelegateAbstract<Element>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var Element
             */
            protected Block $_block;

            protected function loadDelegates(): void
            {
                /** @var SelectorDelegate */
                $element = $this->loadDelegate('element', '~/content/elements/ElementSelector');
                $element
                    ->isForOne(true)
                    ->isRequired($this->_isRequired);
            }

            protected function setDefaultValues(): void
            {
                $id = $this->data->content->element->select('id')
                    ->where('slug', '=', $this->_block->getSlug())
                    ->toValue('id');

                /** @var SelectorDelegate */
                $element = $this['element'];
                $element->setSelected($id);
            }

            public function renderFieldContent(FieldWidget $field): void
            {
                /** @var SelectorDelegate */
                $element = $this['element'];
                $element->renderFieldContent($field);
            }

            public function apply(): Block
            {
                /** @var SelectorDelegate */
                $element = $this['element'];

                $slug = $this->data->content->element->select('slug')
                    ->where('id', '=', $element->apply())
                    ->toValue('slug');

                $this->_block->setSlug($slug);
                return $this->_block;
            }
        };
    }
}
