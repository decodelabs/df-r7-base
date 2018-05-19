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

class Woopra extends Base
{
    protected $_defaultUserAttributes = [];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view)
    {
        $view->linkJs('//static.woopra.com/js/woopra.v2.js');
        $attributes = $handler->getDefinedUserAttributes($this->getDefaultUserAttributes(), true);
        $events = $handler->getEvents();
        $script = '';

        foreach ($attributes as $key => $value) {
            $script .= $this->_createVisitorAttributeString($key, $value);
        }

        $script .= '        woopraTracker.track('.$this->_getTrackArgs($handler).');'."\n";

        foreach ($events as $event) {
            $script .= '        var woopraEvent = new WoopraEvent('.$this->_encodeString($event->getName()).');'."\n".
                $this->_createEventAttributeString('category', $event->getCategory()).
                $this->_createEventAttributeString('label', $event->getLabel());

            foreach ($event->getProperties() as $key => $value) {
                $script .= $this->_createEventAttributeString($key, $value);
            }

            $script .= '        woopraEvent.fire();'."\n";
        }

        // TODO: add ecommerce

        $view->addHeadScript('woopra-analytics', $script);
    }

    protected function _createVisitorAttributeString($key, $value)
    {
        if ($value === null) {
            return null;
        }

        return '        woopraTracker.addVisitorProperty('.$this->_encodeString($key).', '.$this->_encodeString($value).');'."\n";
    }

    protected function _createEventAttributeString($name, $value)
    {
        if ($value === null) {
            return null;
        }

        return '        woopraTracker.addProperty('.$this->_encodeString($name).', '.$this->_encodeString($value).');'."\n";
    }

    protected function _encodeString($string)
    {
        return str_replace('\\/', '/', json_encode($string));
    }

    protected function _getTrackArgs($handler)
    {
        $output = '';
        $url = $handler->getUrl();
        $title = $handler->getTitle();

        if (!$url && !$title) {
            return $output;
        }

        if ($url) {
            $output .= $this->_encodeString($url);
        } else {
            $output .= 'null';
        }

        if ($title) {
            $output .= ', '.$this->_encodeString($title);
        }

        return $output;
    }
}
