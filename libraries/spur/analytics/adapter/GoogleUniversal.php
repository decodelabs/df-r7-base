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

class GoogleUniversal extends Base {
    
    protected $_options = [
        'trackingId' => null,
        'scriptName' => 'ga',
        'createOptions' => [],
    ];

    public function apply(spur\analytics\IHandler $handler, aura\view\IHtmlView $view) {
        $attributes = $handler->getDefinedUserAttributes(
            array_merge($this->getDefaultUserAttributes(), ['id']), 
            false
        );

        $userId = $attributes['id'];
        $map = $this->getDefaultUserAttributeMap();

        foreach($attributes as $attribute => $value) {
            if(!isset($map[$attribute]) || $map[$attribute] === null) {
                unset($attributes[$attribute]);
            }
        }

        $scriptName = $this->getOption('scriptName', 'ga');

        $script = 
            '(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){'."\n".
            '(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),'."\n".
            'm=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)'."\n".
            '})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\''.$scriptName.'\');'."\n";
        
        $script .= $this->_buildCreateCall($scriptName, $userId);
        $pageviewOptions = null;

        foreach($attributes as $attribute => $value) {
            $pageviewOptions[$map[$attribute]] = (string)$value;
        }

        $script .= $scriptName.'(\'send\', \'pageview\'';

        if(!empty($pageviewOptions)) {
            $script .= ', '.json_encode($pageviewOptions);
        }

        $script .= ');'."\n";

        foreach($handler->getEvents() as $event) {
            $script .= $scriptName.'(\'send\', \'event\', \''.$event->getCategory().'\', \''.$event->getName().'\', \''.$event->getLabel().'\');'."\n";
        }

        $view->addHeadScript('google-analytics', rtrim($script));
    }

    public function setTrackingId($id) {
        $this->setOption('trackingId', $id);
        return $this;
    }

    public function getTrackingId() {
        return $this->getOption('trackingId');
    }

    protected function _validateOptions(core\collection\IInputTree $values) {
        $validator = new core\validate\Handler();
        $validator->addField('trackingId', 'text')
                ->isRequired(true)
                ->end()
            ->validate($values);
    }

    protected function _buildCreateCall($scriptName, $userId) {
        $createOptions = $this->getOption('createOptions');

        if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && !isset($createOptions['cookieDomain'])) {
            if(!is_array($createOptions)) {
                $createOptions = [];
            }

            $createOptions['cookieDomain'] = 'none';
        }

        if($userId) {
            if(!is_array($createOptions)) {
                $createOptions = [];
            }

            $createOptions['userId'] = $userId;
        }

        if(empty($createOptions) || !is_array($createOptions)) {
            $createOptions = '\'auto\'';
        } else {
            $createOptions = json_encode($createOptions);
        }

        return $scriptName.'(\'create\', \''.$this->getTrackingId().'\', '.$createOptions.');'."\n";
    }
}