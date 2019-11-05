<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\flex;
use df\aura;

use DecodeLabs\Tagged\Xml\Element as XmlElement;
use DecodeLabs\Tagged\Xml\Writer as XmlWriter;

class Element extends Base
{
    const DEFAULT_CATEGORIES = [];

    protected $_slug;

    public function getFormat(): string
    {
        return 'structure';
    }

    public function setSlug($slug)
    {
        $this->_slug = $slug;
        return $this;
    }

    public function getSlug()
    {
        return $this->_slug;
    }


    public function isEmpty(): bool
    {
        return !strlen($this->_slug);
    }



    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->_slug = $element['slug'];
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['slug'] = $this->_slug;
    }


    // Render
    public function render()
    {
        $view = $this->getView();
        return $view->nightfire->renderElement($this->_slug);
    }


    // Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate
    {
        return new class($this, ...func_get_args()) extends Base_Delegate {
            protected function loadDelegates()
            {
                $this->loadDelegate('element', '~/content/elements/ElementSelector')
                    ->isForOne(true)
                    ->isRequired($this->_isRequired);
            }

            protected function setDefaultValues()
            {
                $id = $this->data->content->element->select('id')
                    ->where('slug', '=', $this->_block->getSlug())
                    ->toValue('id');

                $this['element']->setSelected($id);
            }

            public function renderFieldContent(aura\html\widget\Field $field)
            {
                $this['element']->renderFieldContent($field);

                return $this;
            }

            public function apply()
            {
                $slug = $this->data->content->element->select('slug')
                    ->where('id', '=', $this['element']->apply())
                    ->toValue('slug');

                $this->_block->setSlug($slug);
                return $this->_block;
            }
        };
    }
}
