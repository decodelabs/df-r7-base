<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;
    
class Group implements IGroup, core\IDumpable {

    protected $_bit;
    protected $_name;
    protected $_displayOrder = 1;
    protected $_subscribers = 0;
    protected $_set;

    public function __construct(IGroupSet $set, array $apiData) {
        $this->_set = $set;
        $this->_bit = $apiData['bit'];
        $this->_name = $apiData['name'];
        $this->_displayOrder = $apiData['display_order'];
        $this->_subscribers = $apiData['subscribers'];
    }

    public function getMediator() {
        return $this->_set->getMediator();
    }

    public function getGroupSet() {
        return $this->_set;
    }

    public function getBit() {
        return $this->_bit;
    }

    public function getName() {
        return $this->_name;
    }

    public function getDisplayOrder() {
        return $this->_displayOrder;
    }

    public function countSubscribers() {
        return $this->_subscribers;
    }



// Entry
    public function rename($newName) {
        $mediator = $this->_set->getMediator();
        
        if($mediator->callServer('listInterestGroupUpdate', $this->_set->getListId(), $this->_name, $newName, $this->_set->getId())) {
            $this->_name = $newName;
        }

        return $this;
    }

    public function delete() {
        $mediator = $this->_set->getMediator();

        if($mediator->callServer('listInterestGroupDel', $this->_set->getListId(), $this->_name, $this->_set->getId())) {
            $this->_set->_removeGroup($this);
        }

        return $this;
    }


// Dump
    public function getDumpProperties() {
        return [
            'set' => $this->_set->getId(),
            'bit' => $this->_bit,
            'name' => $this->_name,
            'displayOrder' => $this->_displayOrder,
            'subscribers' => $this->_subscribers
        ];
    }
}