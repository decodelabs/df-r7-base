<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp;

use df;
use df\core;
use df\spur;
    
class WebHook implements IWebHook, core\IDumpable {

    protected static $_availableActions = [
        'subscribe', 'unsubscribe', 'profile', 'cleaned', 'upemail', 'campaign'
    ];

    protected static $_availableSources = [
        'user', 'admin', 'api'
    ];

    protected $_listId;
    protected $_url;
    protected $_actions = [];
    protected $_sources = [];
    protected $_mediator;

    public static function getAvailableActions() {
        return self::$_availableActions;
    }

    public static function normalizeActions(array $actions) {
        if(empty($actions)) {
            throw new InvalidArgumentException(
                'WebHook action list must contain at least one action'
            );
        }

        $output = [];

        foreach($actions as $key => $value) {
            if(is_numeric($key)) {
                $action = $value;
                $active = true;
            } else {
                $action = $key;
                $active = $value;
            }

            if(!in_array($action, self::$_availableActions)) {
                throw new InvalidArgumentException(
                    $action.' is an invalid WebHook action'
                );
            }

            $output[$action] = $active;
        }

        foreach(self::$_availableActions as $action) {
            if(!isset($output[$action])) {
                $output[$action] = false;
            }
        }

        return $output;
    }

    public static function getAvailableSources() {
        return self::$_availableSources;
    }

    public static function normalizeSources(array $sources) {
        if(empty($sources)) {
            throw new InvalidArgumentException(
                'WebHook source list must contain at least one source'
            );
        }

        $output = [];

        foreach($sources as $key => $value) {
            if(is_numeric($key)) {
                $source = $value;
                $active = true;
            } else {
                $source = $key;
                $active = $value;
            }

            if(!in_array($source, self::$_availableSources)) {
                throw new InvalidArgumentException(
                    $source.' is an invalid WebHook source'
                );
            }

            $output[$source] = $active;
        }

        foreach(self::$_availableSources as $source) {
            if(!isset($output[$source])) {
                $output[$source] = false;
            }
        }

        return $output;
    }

    public function __construct(IMediator $mediator, $listId, core\collection\ITree $apiData) {
        $this->_mediator = $mediator;
        $this->_listId = $listId;
        $this->_url = $apiData['url'];
        $this->_actions = $apiData->actions->toArray();
        $this->_sources = $apiData->sources->toArray();
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function getListId() {
        return $this->_listId;
    }

    public function getUrl() {
        return $this->_url;
    }

    public function getActions() {
        return $this->_actions;
    }

    public function getSources() {
        return $this->_sources;
    }



// Entry
    public function delete() {
        $this->_mediator->deleteWebHook($this->_listId, $this->_url);
        return $this;
    }


// Dump
    public function getDumpProperties() {
        $sources = $actions = [];

        foreach($this->_actions as $action => $active) {
            if($active) {
                $actions[] = $action;
            }
        }

        foreach($this->_sources as $source => $active) {
            if($active) {
                $sources[] = $source;
            }
        }

        return [
            'list' => $this->_listId,
            'url' => $this->_url,
            'actions' => $actions,
            'sources' => $sources
        ];
    }
}