<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\arch;
use df\aura;
use df\core;

class FieldSet extends Container implements IFieldSetWidget, IWidgetShortcutProvider
{
    use core\constraint\TDisableable;

    public const PRIMARY_TAG = 'fieldset';

    protected $_legendBody;
    protected $_legendTagName = 'legend';
    protected $_name;
    protected $_targetFormId;

    public function __construct(arch\IContext $context, $legend = null)
    {
        parent::__construct($context);

        if ($legend instanceof aura\html\IElementContent) {
            $this->setLegendBody($legend);
        } else {
            $this->_legendBody = new aura\html\ElementContent($legend, $this->getTag());
        }
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $children = $this->_prepareChildren();

        if ($this->_name !== null) {
            $tag->setAttribute('name', $this->_name);
        }

        if ($this->_targetFormId !== null) {
            $tag->setAttribute('form', $this->_targetFormId);
        }

        if ($this->_isDisabled) {
            $tag->setAttribute('disabled', 'disabled');
        }

        $legend = null;

        if (!$this->_legendBody->isEmpty()) {
            $legendBody = $this->_legendBody;

            if ($this->_legendTagName == 'legend') {
                $legendBody = new aura\html\Element('span', $legendBody);
            }

            $legend = (new aura\html\Element($this->_legendTagName, $legendBody))->render();
        }

        return $tag->renderWith(
            [
                $legend,
                (new aura\html\Tag('div', ['class' => 'body']))->renderWith($children)
            ],
            true
        );
    }


    // Legend
    public function setLegendBody(aura\html\IElementContent $body)
    {
        if (!$body->getParentRenderContext()) {
            $body->setParentRenderContext($this->getTag());
        }

        $this->_legendBody = $body;
        return $this;
    }

    public function getLegendBody()
    {
        return $this->_legendBody;
    }

    public function setLegendTagName($tagName)
    {
        $this->_legendTagName = $tagName;
        return $this;
    }

    public function getLegendTagName()
    {
        return $this->_legendTagName;
    }


    // Name
    public function setName(?string $name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }


    // Target form
    public function setTargetFormId($id)
    {
        $this->_targetFormId = $id;
        return $this;
    }

    public function getTargetFormId()
    {
        return $this->_targetFormId;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_name,
            '*form' => $this->_targetFormId,
            '*legend' => $this->_legendBody,
            '%tag' => $this->getTag()
        ];

        yield 'values' => $this->_children->toArray();
    }
}
