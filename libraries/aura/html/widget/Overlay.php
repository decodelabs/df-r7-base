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

    const PRIMARY_TAG = 'div.overlay';

    protected $_titleBody;
    protected $_titleTagName = 'h3';
    protected $_url;

    public function __construct(arch\IContext $context, $title=null, $url=null) {
        parent::__construct($context);

        if($title instanceof aura\html\IElementContent) {
            $this->setTitleBody($title);
        } else {
            $this->_titleBody = new aura\html\ElementContent($title, $this->getTag());
        }

        $this->setUrl($url);
    }

    protected function _render() {
        $tag = $this->getTag();
        $title = null;

        if(!$this->_titleBody->isEmpty()) {
            $title = (new aura\html\Element($this->_titleTagName, $this->_titleBody))->render();
        }

        $children = $this->_prepareChildren();

        if($this->_url !== null) {
            $children->prepend((new aura\html\Tag('iframe', [
                'src' => $this->_context->uri($this->_url),
                'width' => '100%',
                'frameborder' => '0'
            ]))->render());
        }

        return $tag->renderWith(
            (new aura\html\Tag('div', ['class' => 'container']))->renderWith([
                $title,
                (new aura\html\Tag('div', ['class' => 'body']))->renderWith($children)
            ]),
            true
        );
    }


// Url
    public function setUrl($url) {
        $this->_url = $url;
        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }


// Title
    public function withTitleBody() {
        return new aura\html\widget\util\ElementContentWrapper($this, $this->_titleBody);
    }

    public function setTitleBody(aura\html\IElementContent $body) {
        if(!$body->getParentRenderContext()) {
            $body->setParentRenderContext($this->getTag());
        }

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
            'tag' => $this->getTag()
        ];
    }
}
