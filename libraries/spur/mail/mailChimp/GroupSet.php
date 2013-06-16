<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;
    
class GroupSet implements IGroupSet {

    protected $_listId;
    protected $_id;
    protected $_name;
    protected $_formFieldType;
    protected $_displayOrder = 0;
    protected $_groups = array();
    protected $_mediator;

    public function __construct(IMediator $mediator, $listId, array $apiData) {
        $this->_mediator = $mediator;
        $this->_listId = $listId;
        $this->_id = $apiData['id'];
        $this->_name = $apiData['name'];
        $this->_formFieldType = $apiData['form_field'];
        $this->_displayOrder = $apiData['display_order'];

        foreach($apiData['groups'] as $groupData) {
            $group = new Group($this, $groupData);
            $this->_groups[$group->getBit()] = $group;
        }
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function getListId() {
        return $this->_listId;
    }

    public function getId() {
        return $this->_id;
    }

    public function getName() {
        return $this->_name;
    }

    public function getFormFieldType() {
        return $this->_formFieldType;
    }

    public function getDisplayOrder() {
        return $this->_displayOrder;
    }

    public function getGroups() {
        return $this->_groups;
    }

    public function addGroup($name) {
        $this->_mediator->callServer('listInterestGroupAdd', $this->_listId, $name, $this->_id);

        $bit = max(array_keys($this->_groups)) + 1;
        $this->_groups[$bit] = new Group($this, [
            'bit' => $bit,
            'name' => $name,
            'display_order' => $bit,
            'subscribers' => 0
        ]);

        return $this;
    }

    public function _removeGroup($bit) {
        if($bit instanceof IGroup) {
            $bit = $bit->getBit();
        }

        unset($this->_groups[$bit]);
        return $this;
    }


    public function rename($newName) {
        $mediator = $this->_set->getMediator();
        
        if($mediator->callServer('listInterestGroupingUpdate', $this->_id, 'name', $newName)) {
            $this->_name = $newName;
        }

        return $this;
    }

    public function delete() {
        $mediator = $this->_set->getMediator();
        $mediator->callServer('listInterestGroupingDel', $this->_id);

        return $this;
    }
}