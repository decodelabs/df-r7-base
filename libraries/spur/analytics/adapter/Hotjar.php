<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics\adapter;

use df;
use df\core;
use df\spur;
use df\aura;

class Hotjar extends Base
{
    protected $_options = [
        'trackingId' => null
    ];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view)
    {
        if (!$view->consent->has('statistics')) {
            return;
        }

        $view->addScript(
            'hotjar-analytics',
            '(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:'.$this->getTrackingId().',hjsv:6};a=o.getElementsByTagName(\'head\')[0];r=o.createElement(\'script\');r.async=1;r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;a.appendChild(r);})(window,document,\'//static.hotjar.com/c/hotjar-\',\'.js?sv=\');'
        );
    }

    public function setTrackingId($id)
    {
        $this->setOption('trackingId', $id);
        return $this;
    }

    public function getTrackingId()
    {
        return $this->getOption('trackingId');
    }

    protected function _validateOptions(core\collection\IInputTree $values)
    {
        $validator = new core\validate\Handler();
        $validator->addRequiredField('trackingId', 'text')->validate($values);
    }
}
