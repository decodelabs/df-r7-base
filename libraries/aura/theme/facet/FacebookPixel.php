<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme\facet;

use df\aura;

class FacebookPixel extends Base
{
    protected $_id;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->_id = $config['id'] ?? null;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function afterHtmlViewRender(aura\view\IHtmlView $view)
    {
        if (!$this->_id || !$this->_checkEnvironment()) {
            return;
        }

        $script =
            '!function(f,b,e,v,n,t,s)' . "\n" .
            '{if(f.fbq)return;n=f.fbq=function(){n.callMethod?' . "\n" .
            'n.callMethod.apply(n,arguments):n.queue.push(arguments)};' . "\n" .
            'if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';' . "\n" .
            'n.queue=[];t=b.createElement(e);t.async=!0;' . "\n" .
            't.src=v;s=b.getElementsByTagName(e)[0];' . "\n" .
            's.parentNode.insertBefore(t,s)}(window, document,\'script\',' . "\n" .
            '\'https://connect.facebook.net/en_US/fbevents.js\');' . "\n" .
            'fbq(\'init\', \'' . $this->_id . '\');' . "\n" .
            'fbq(\'track\', \'PageView\');';

        $noScript =
            '<img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $this->_id . '&ev=PageView&noscript=1"/>';

        $view->addHeadScript('facebookPixel', $script, null, $noScript);
    }
}
