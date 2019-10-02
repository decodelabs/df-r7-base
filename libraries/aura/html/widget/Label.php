<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flex;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Label extends Base implements ILabelWidget, Inspectable
{
    use TWidget_BodyContentAware;

    const PRIMARY_TAG = 'label';

    protected $_inputId;

    public function __construct(arch\IContext $context, $body, $inputId=null)
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
                $widget->setId($inputId = 'input-'.flex\Generator::random());
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*for' => $inspector($this->_inputId),
                '%tag' => $inspector($this->getTag())
            ])
            ->setValues($inspector->inspectList($this->_body->toArray()));
    }
}
