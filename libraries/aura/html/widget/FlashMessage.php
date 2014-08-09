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
use df\flow;
    
class FlashMessage extends Base {

    const PRIMARY_TAG = 'div';

    protected $_message;

    public function __construct(arch\IContext $context, $message, $type=null) {
        $this->setMessage($message, $type);
    }


    protected function _render() {
        $view = $this->getRenderTarget()->getView();
        $tag = $this->getTag();

        $tag->addClass($this->_message->getType());

        $title = new aura\html\Element('h4', [
                $view->html->icon($this->_message->getType()), ' ',
                $this->_message->getMessage()
            ]);

        if($description = $this->_message->getDescription()) {
            $description = new aura\html\Element('p', $description, [
                'class' => 'description'
            ]);
        } else {
            $description = null;
        }

        $body = [$title, $description];

        if($link = $this->_message->getLink()) {
            $body = new aura\html\Element('a', $body, [
                'href' => $view->uri->to($link),
                'title' => $this->_message->getLinkText()
            ]);
        }

        return $tag->renderWith($body);
    }


    public function setMessage($message, $type=null) {
        $this->_message = clone flow\FlashMessage::factory(null, $message, $type);
        return $this;
    }

    public function getMessage() {
        return $this->_message;
    }

    public function setType($type) {
        $this->_message->setType($type);
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function setDescription($description) {
        $this->_message->setDescription($description);
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setLink($link, $text=null) {
        $this->_message->setLink($link, $text);
        return $this;
    }
}