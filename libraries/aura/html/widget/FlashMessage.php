<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use df\arch;
use df\aura;
use df\flow;

class FlashMessage extends Base
{
    protected $_message;
    protected $_showIcon = true;

    public function __construct(arch\IContext $context, $message, $type = null)
    {
        parent::__construct($context);

        $this->setMessage($message, $type);
    }


    protected function _getPrimaryTagType(): string
    {
        if ($this->_message->getLink() !== null) {
            return 'a.flashMessage';
        } else {
            return 'div.flashMessage';
        }
    }


    protected function _render()
    {
        $tag = $this->getTag();
        $tag->addClass($this->_message->getType());

        $icon = $this->_showIcon ?
            $this->_context->html->icon($this->_message->getType()) :
            null;

        $title = new aura\html\Element('p.message', $this->_message->getMessage());

        if ($description = $this->_message->getDescription()) {
            $description = new aura\html\Element('p.description', $description);
        } else {
            $description = null;
        }

        if ($link = $this->_message->getLink()) {
            $tag->addAttributes([
                'href' => $url = $this->_context->uri->__invoke($link),
                'title' => $this->_message->getLinkText()
            ]);

            if ($this->_message->shouldLinkOpenInNewWindow()) {
                $tag->setAttribute('target', '_blank');
            }
        }

        return $tag->renderWith([$icon, $title, $description]);
    }


    public function setMessage($message, $type = null)
    {
        $this->_message = clone flow\FlashMessage::factory(uniqid(), $message, $type);
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setType($type)
    {
        $this->_message->setType($type);
        return $this;
    }

    public function getType()
    {
        return $this->_message->getType();
    }

    public function setDescription($description)
    {
        $this->_message->setDescription($description);
        return $this;
    }

    public function getDescription()
    {
        return $this->_message->getDescription();
    }

    public function setLink($link, $text = null)
    {
        $this->_message->setLink($link, $text);
        return $this;
    }

    public function shouldLinkOpenInNewWindow(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_message->shouldLinkOpenInNewWindow($flag);
            return $this;
        }

        return $this->_message->shouldLinkOpenInNewWindow();
    }

    public function shouldShowIcon(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_showIcon = $flag;
            return $this;
        }

        return $this->_showIcon;
    }
}
