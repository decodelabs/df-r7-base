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

class Heap extends Base
{
    protected $_options = [
        'trackingId' => null
    ];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view)
    {
        if (!$view->consent->has('statistics')) {
            return;
        }

        $script = 'window.heap=window.heap||[],heap.load=function(e,t){window.heap.appid=e,window.heap.config=t=t||{};var n=t.forceSSL||"https:"===document.location.protocol,a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src=(n?"https:":"http:")+"//cdn.heapanalytics.com/js/heap-"+e+".js";var o=document.getElementsByTagName("script")[0];o.parentNode.insertBefore(a,o);for(var r=function(e){return function(){heap.push([e].concat(Array.prototype.slice.call(arguments,0)))}},p=["clearEventProperties","identify","setEventProperties","track","unsetEventProperty"],c=0;c<p.length;c++)heap[p[c]]=r(p[c])};'."\n";
        $script .= 'heap.load("'.$this->getTrackingId().'");';

        $view->addHeadScript('heap-analytics', $script);
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
