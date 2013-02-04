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
    
class Overlay extends Container implements IWidgetShortcutProvider {

    const PRIMARY_TAG = 'div';

    protected $_titleBody;
    protected $_titleTagName = 'h3';

    public function __construct(arch\IContext $context, $title=null) {
        parent::__construct($context);

        if($title instanceof aura\html\IElementContent) {
            $this->setTitleBody($title);
        } else {
            $this->_titleBody = new aura\html\ElementContent($title);
        }
    }

    protected function _render() {
        $tag = $this->getTag();
        $title = null;

        if(!$this->_titleBody->isEmpty()) {
            $title = (new aura\html\Element($this->_titleTagName, $this->_titleBody))->render();
        }

        return $tag->renderWith(
            (new aura\html\Tag('div', ['class' => 'container']))->renderWith([
                $title, 
                (new aura\html\Tag('div', ['class' => 'body']))->renderWith($this->_children)
            ]),
            true
        );
    }


// Title
    public function withTitleBody() {
        return new aura\html\widget\util\ElementContentWrapper($this, $this->_titleBody);
    }

    public function setTitleBody(aura\html\IElementContent $body) {
        $this->_titleBody = $body;
        return $this;
    }

    public function getTitleBody() {
        return $this->_titleBody;
    }


    public function setTitleTagName($name) {
        $this->_titleTagName = $name;
        return $this;
    }

    public function getTitleTagName() {
        return $this->_titleTagName;
    }


// Dump
    public function getDumpProperties() {
        return [
            'title' => $this->_titleBody,
            'children' => $this->_children,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}