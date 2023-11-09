<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme\facet;

use DecodeLabs\Genesis;

use df\aura;

class GoogleTagManager extends Base
{
    protected $_id;
    protected $_devAuth = null;
    protected $_devEnv = null;
    protected $_checkCookies = null;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->_id = $config['id'] ?? null;
        $this->_devAuth = $config['devAuth'] ?? null;
        $this->_devEnv = $config['devEnv'] ?? null;
        $this->_checkCookies = $config['cookies'] ?? null;
    }

    public function setId(string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }



    public function setDevAuth(?string $auth)
    {
        $this->_devAuth = $auth;
        return $this;
    }

    public function getDevAuth(): ?string
    {
        return $this->_devAuth;
    }

    public function setDevEnv(?string $env)
    {
        $this->_devEnv = $env;
        return $this;
    }

    public function getDevEnv(): ?string
    {
        return $this->_devEnv;
    }



    public function onHtmlViewLayoutRender(aura\view\IHtmlView $view, $content)
    {
        if (
            !$this->_checkEnvironment() ||
            (
                $this->_checkCookies !== null &&
                !$view->consent->has('statistics', $this->_checkCookies)
            )
        ) {
            return;
        }

        $devSuffix = $devQuery = '';

        if (
            !Genesis::$environment->isProduction() &&
            !empty($this->_devAuth) &&
            !empty($this->_devEnv)
        ) {
            $devQuery = '&gtm_auth=' . $this->_devAuth . '&gtm_preview=' . $this->_devEnv . '&gtm_cookies_win=x';
            $devSuffix = "+ '" . $devQuery . "'";
        }

        $view->addScript(
            'gtm',
            '(function(w,d,s,l,i){' . "\n" .
            'var h=\'\';var n=d.querySelector(\'[nonce]\'); h=(n&&(n.nonce||n.getAttribute(\'nonce\')));' . "\n" .
            'w[l]=w[l]||[];w[l].push({\'gtm.start\':' . "\n" .
            'new Date().getTime(),event:\'gtm.js\',nonce:h});var f=d.getElementsByTagName(s)[0],' . "\n" .
            'j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=' . "\n" .
            '\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl' . $devSuffix . ';' . "\n" .
            'j.setAttribute(\'nonce\',h);f.parentNode.insertBefore(j,f);' . "\n" .
            '})(window,document,\'script\',\'dataLayer\',\'' . $this->_id . '\');'
        );

        $content =
            '<!-- Google Tag Manager (noscript) -->' . "\n" .
            '<noscript><iframe src="//www.googletagmanager.com/ns.html?id=' . $this->_id . $devQuery . '"' . "\n" .
            'height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n" .
            '<!-- End Google Tag Manager (noscript) -->' . "\n" .
            $content
        ;

        return $content;
    }
}
