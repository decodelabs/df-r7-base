<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\flow;
use df\arch;

class Mail extends flow\mail\Message implements ILayoutView {

    use TView;
    use TView_Layout;

    const DEFAULT_LAYOUT = 'Default';

    protected $_textMode = false;
    protected $_hasRendered = false;

    public function __construct($type, arch\IContext $context) {
        $this->_type = $type;
        $this->context = $context;

        parent::__construct('(no subject)', null);
    }

    public function isPlainText(bool $flag=null) {
        if($flag !== null) {
            $this->_textMode = $flag;
            return $this;
        }

        return $this->_textMode;
    }

    public function getBodyText() {
        if(!$this->_hasRendered) {
            $this->render();
        }

        return parent::getBodyText();
    }

    public function getBodyHtml() {
        if(!$this->_hasRendered) {
            $this->render();
        }

        return parent::getBodyHtml();
    }

    public function shouldUseLayout(bool $flag=null) {
        if($flag !== null) {
            $this->_useLayout = $flag;
            return $this;
        }

        return !$this->_textMode && $this->_useLayout;
    }

    protected function _beforeRender() {
        if($this->_hasRendered) {
            throw new RuntimeException(
                'Mail views can only render once'
            );
        }

        $this->getTheme()->beforeViewRender($this);
    }

    protected function _onContentRender($content) {
        $content = $this->getTheme()->onViewContentRender($this, $content);

        /*
        if(!$this->_textMode && $this->_bodyText === null) {
            // Turn this on when it works properly :)
            $this->setBodyText($this->context->html->toText($content));
        }
        */

        return $content;
    }

    protected function _afterRender($content) {
        $content = $this->getTheme()->afterViewRender($this, $content);

        if($this->_textMode) {
            $this->setBodyText($content);
        } else {
            $this->setBodyHtml($content);
        }

        $this->content = null;
        $this->slots = [];

        $this->_hasRendered = true;
        return $content;
    }
}