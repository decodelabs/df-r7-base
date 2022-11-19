<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\flex;

class Label extends Base implements ILabelWidget, Dumpable
{
    use TWidget_BodyContentAware;

    public const PRIMARY_TAG = 'label';

    protected $_inputId;

    public function __construct(arch\IContext $context, $body, $inputId = null)
    {
        parent::__construct($context);

        $this->setInputId($inputId);
        $this->setBody($body);
    }


    protected function _render()
    {
        $tag = $this->getTag();

        if (!$this->hasBody()) {
            $tag->addClass('empty');
        }

        if ($this->_inputId !== null) {
            $tag->setAttribute('for', $this->_inputId);
        }

        return $tag->renderWith($this->_body);
    }

    public function setInputId($inputId)
    {
        if ($inputId instanceof IWidget) {
            $widget = $inputId;
            $inputId = $widget->getId();

            if (!$inputId) {
                $widget->setId($inputId = 'input-' . flex\Generator::random());
            }
        }

        $this->_inputId = $inputId;
        return $this;
    }

    public function getInputId()
    {
        return $this->_inputId;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*for' => $this->_inputId,
            '%tag' => $this->getTag()
        ];

        yield 'values' => $this->_body->toArray();
    }
}
