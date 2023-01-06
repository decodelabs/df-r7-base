<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use df\arch;
use df\aura;
use df\aura\view\IHtmlView as View;
use df\spur;

class Recaptcha extends Base
{
    public const PRIMARY_TAG = 'div.recaptcha';

    protected $_siteKey = null;
    protected ?View $_view = null;

    public function __construct(arch\IContext $context, $siteKey = null)
    {
        parent::__construct($context);
        $this->setSiteKey($siteKey);
    }

    public function setSiteKey($key)
    {
        $this->_siteKey = $key;
        return $this;
    }

    public function getSiteKey()
    {
        return $this->_siteKey;
    }

    public function setView(?View $view): static
    {
        $this->_view = $view;
        return $this;
    }

    public function getView(): ?View
    {
        return $this->_view;
    }

    protected function _render()
    {
        if ($this->_siteKey !== null) {
            $key = $this->_siteKey;
        } else {
            $config = spur\auth\recaptcha\Config::getInstance();

            if (!$config->isEnabled()) {
                return '';
            }

            $key = $config->getSiteKey();
        }

        $tag = $this->getTag()
            ->setDataAttribute('sitekey', $key)
            ->setClass('g-recaptcha');

        $nonce = null;

        if ($csp = $this->getContext()->app->getCsp('text/html')) {
            $nonce = $csp->getNonce();
        }

        $output = '';


        if ($this->_view) {
            $this->_view->linkJs('https://www.google.com/recaptcha/api.js');
        } else {
            $script = new aura\html\Tag('script', [
                'src' => 'https://www.google.com/recaptcha/api.js'
            ]);

            if ($nonce !== null) {
                $script->setAttribute('nonce', $nonce);
            }

            $output .= $script;
        }

        $output .= $tag->render();
        return $output;
    }
}
