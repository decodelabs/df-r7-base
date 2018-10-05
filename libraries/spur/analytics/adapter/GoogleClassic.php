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

class GoogleClassic extends Base implements spur\analytics\ILegacyAdapter
{
    protected $_options = [
        'trackingId' => null
    ];

    protected $_defaultUserAttributes = [];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view)
    {
        if (!$view->consent->has('statistics')) {
            return;
        }

        $attributes = $handler->getDefinedUserAttributes($this->getDefaultUserAttributes(), false);
        $events = $handler->getEvents();
        $script = 'var _gaq = _gaq || [];'."\n".
            $this->_createCallString('_setAccount', [$this->getTrackingId()])."\n";

        ksort($attributes);
        $i = 1;

        foreach ($attributes as $key => $value) {
            $script .= $this->_createCallString('_setCustomVar', [$i, $key, $value, 1])."\n";

            if ($i++ >= 5) {
                break;
            }
        }

        $trackVars = [];

        if ($url = $handler->getUrl()) {
            $trackVars[] = $url;
        }

        $script .= $this->_createCallString('_trackPageview', $trackVars)."\n";

        foreach ($events as $event) {
            $eventArr = [$event->getCategory(), $event->getName(), $event->getLabel()];
            $script .= $this->_createCallString('_trackEvent', $eventArr)."\n";
        }

        $script .= "\n";
        $script .=
            '(function() {'."\n".
            '    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;'."\n".
            '    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';'."\n".
            '    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);'."\n".
            '})();'."\n"
            ;

        $view->addHeadScript('google-analytics', $script);
    }

    protected function _createCallString($method, array $args=[])
    {
        array_unshift($args, $method);
        return '_gaq.push('.str_replace('"', '\'', json_encode($args)).');';
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
