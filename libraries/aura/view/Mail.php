<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\view;

use DecodeLabs\Exceptional;
use DecodeLabs\Metamorph;

use DecodeLabs\Tagged\Mail\Generator;
use df\arch;
use df\flow;

class Mail extends flow\mail\Message implements ILayoutView
{
    use TView;
    use TView_Layout;

    public const DEFAULT_LAYOUT = 'Default';

    public $generator;

    protected $_textMode = false;
    protected $_hasRendered = false;

    public function __construct($type, arch\IContext $context)
    {
        $this->_type = $type;
        $this->context = $context;

        parent::__construct('(no subject)', null);
        $this->generator = new Generator();
    }

    public function isPlainText(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_textMode = $flag;
            return $this;
        }

        return $this->_textMode;
    }

    public function getBodyText()
    {
        if (!$this->_hasRendered) {
            $this->render();
        }

        return parent::getBodyText();
    }

    public function getBodyHtml()
    {
        if (!$this->_hasRendered) {
            $this->render();
        }

        return parent::getBodyHtml();
    }

    public function shouldUseLayout(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_useLayout = $flag;
            return $this;
        }

        return !$this->_textMode && $this->_useLayout;
    }

    protected function _beforeRender()
    {
        if ($this->_hasRendered) {
            throw Exceptional::Runtime(
                'Mail views can only render once'
            );
        }

        $this->getTheme()->beforeViewRender($this);
        $this->setSlot('generator', $this->generator);
    }

    protected function _onContentRender($content)
    {
        $content = $this->getTheme()->onViewContentRender($this, $content);

        /*
        if(!$this->_textMode && $this->_bodyText === null) {
            // Turn this on when it works properly :)
            $this->setBodyText(Metamorph::htmlToText($content));
        }
         */

        return $content;
    }

    protected function _afterRender($content)
    {
        $content = $this->getTheme()->afterViewRender($this, $content);

        if ($this->_textMode) {
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
