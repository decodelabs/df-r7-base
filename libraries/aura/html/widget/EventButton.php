<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Dictum;
use df\arch;

use df\aura;

class EventButton extends Button
{
    public const PRIMARY_TAG = 'button.btn.event';
    public const BUTTON_TYPE = 'submit';

    public function __construct(arch\IContext $context, $event, $body = null)
    {
        parent::__construct($context, 'formEvent', $body, $event);
    }

    protected function _render()
    {
        $event = $this->getEvent();

        if ($this->_body->isEmpty()) {
            $this->_body->push(Dictum::name($event));
        }

        $parts = explode('(', $event);
        $parts = explode('.', (string)array_shift($parts));
        $this->addClass((string)array_shift($parts));

        return parent::_render();
    }

    public function setEvent($event)
    {
        return $this->setValue($event);
    }

    public function getEvent()
    {
        return $this->getValue();
    }

    public function setValue($event)
    {
        if ($event == 'cancel'
        || $event == 'reset') {
            $this->shouldValidate(false);
        }


        return parent::setValue($event);
    }

    protected function _generateIcon()
    {
        $output = parent::_generateIcon();

        if (!$output) {
            $output = new aura\html\Element('span.hidden', null, ['aria-hidden' => true]);
        }

        $output->setDataAttribute('button-event', $this->getEvent());
        return $output;
    }
}
