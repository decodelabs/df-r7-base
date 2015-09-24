<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList;

use df;
use df\core;
use df\flow;
use df\user;

class Source implements ISource {
    
    protected $_id;
    protected $_adapter;
    protected $_primaryListId;

    public function __construct($id, $options) {
        $options = core\collection\Tree::factory($options);

        $this->_id = $id;
        $this->_adapter = flow\mailingList\adapter\Base::factory($options);
        $this->_primaryListId = $options['primaryList'];
    }

    public function getId() {
        return $this->_id;
    }

    public function getAdapter() {
        return $this->_adapter;
    }

    public function canConnect() {
        return $this->_adapter->canConnect();
    }


    public function getManifest() {
        $cache = Cache::getInstance();

        if(!$manifest = $cache->get('source:'.$this->_id)) {
            $manifest = $this->_adapter->fetchManifest();
            $manifest = $this->_normalizeManifest($manifest);
            $cache->set('source:'.$this->_id, $manifest);
        }

        return $manifest;
    }

    protected function _normalizeManifest($manifest) {
        if(!is_array($manifest)) {
            $manifest = [];
        }

        foreach($manifest as $id => $list) {
            if(!isset($list['name'])) {
                $list['name'] = $this->_adapter->getName().': '.$list['id'];
            }

            if(!isset($list['groupSets'])) {
                $list['groupSets'] = [];
            }

            if(!isset($list['groups'])) {
                $list['groups'] = [];
            }

            foreach($list['groups'] as $groupId => $group) {
                if(!is_array($group)) {
                    $group = ['name' => (string)$group];
                }

                if(!isset($group['name'])) {
                    $group['name'] = 'Group: '.$groupId;
                }

                if(!isset($group['groupSet'])) {
                    $group['groupSet'] = null;
                }

                if(!isset($group['subscribers'])) {
                    $group['subscribers'] = null;
                }

                $list['groups'][$groupId] = $group;
            }

            if(!isset($list['subscribers'])) {
                $list['subscribers'] = null;
            }

            $manifest[$id] = $list;
        }

        return $manifest;
    }


    public function getPrimaryListId() {
        return $this->_primaryListId;
    }

    public function getPrimaryListManifest() {
        if(!$this->_primaryListId) {
            return null;
        }

        $manifest = $this->getManifest();

        if(isset($manifest[$this->_primaryListId])) {
            return $manifest[$this->_primaryListId];
        }
    }




    public function getAvailableLists() {
        $output = [];

        foreach($this->getManifest() as $listId => $list) {
            $output[$listId] = $list['name'];
        }

        return $output;
    }

    public function getAvailableGroupSetList() {
        $output = [];

        foreach($this->getManifest() as $listId => $list) {
            foreach($list['groupSets'] as $setId => $setName) {
                $output[$listId.'/'.$setId] = $setName;
            }
        }

        return $output;
    }

    public function getAvailableGroupList() {
        $output = [];

        foreach($this->getManifest() as $listId => $list) {
            foreach($list['groups'] as $groupId => $group) {
                $output[$listId.'/'.$groupId] = $list['name'].' / '.$group['name'];
            }
        }

        return $output;
    }



    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $groups=null, $replace=true) {
        $manifest = $this->getManifest();

        if(!isset($manifest[$listId])) {
            throw new RuntimeException('List id '.$listId.' could not be found on source '.$this->_id);
        }

        $this->_adapter->subscribeUserToList($client, $listId, $manifest[$listId], $groups, $replace);
        return $this;
    }
}