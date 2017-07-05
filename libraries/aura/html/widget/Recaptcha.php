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
use df\spur;

class Recaptcha extends Base {

    const PRIMARY_TAG = 'div.recaptcha';

    protected $_siteKey = null;

    public function __construct(arch\IContext $context, $siteKey=null) {
        parent::__construct($context);
        $this->setSiteKey($siteKey);
    }

    public function setSiteKey($key) {
        $this->_siteKey = $key;
        return $this;
    }

    public function getSiteKey() {
        return $this->_siteKey;
    }

    protected function _render() {
        if($this->_siteKey !== null) {
            $key = $this->_siteKey;
        } else {
            $config = spur\auth\recaptcha\Config::getInstance();

            if(!$config->isEnabled()) {
                return '';
            }

            $key = $config->getSiteKey();
        }

        $tag = $this->getTag()
            ->setDataAttribute('sitekey', $key)
            ->setClass('g-recaptcha');

        $script = new aura\html\Tag('script', [
            'src' => 'https://www.google.com/recaptcha/api.js'
        ]);

        return $script->render().$tag->render();
    }
}
