<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\event;

use df;
use df\core;
use df\mesh;
    
class Event implements IEvent {

    use core\collection\TArrayCollection_Map;

    protected $_action;
    protected $_entityLocator;
    protected $_entity;

    public function __construct($entity, $action, array $data=null) {
        if(!$entity instanceof mesh\entity\ILocatorProvider) {
            $entity = mesh\entity\Locator::factory($entity);
        }

        $this->setEntity($entity);
        $this->setAction($action);

        if($data !== null) {
            $this->import($data);
        }
    }


// Entity
    public function setEntity(mesh\entity\ILocatorProvider $entity) {
        if($entity instanceof mesh\entity\IEntity) {
            $this->_entity = $entity;
        }

        $this->_entityLocator = $entity->getEntityLocator();
        return $this;
    }

    public function hasEntity() {
        return $this->_entity !== null;
    }

    public function getEntity() {
        if(!$this->_entity) {
            if(!$this->_entityLocator) {
                return null;
            }

            $this->_entity = mesh\Manager::getInstance()->fetchEntity($this->_entityLocator);
        }

        return $this->_entity;
    }

    public function getEntityLocator() {
        return $this->_entityLocator;
    }

    public function hasCachedEntity() {
        return $this->_entity !== null;
    }

    public function getCachedEntity() {
        return $this->_entity;
    }

    public function clearCachedEntity() {
        $this->_entity = null;
        return $this;
    }


// Action
    public function setAction($action) {
        $this->_action = lcfirst($action);
        return $this;
    }

    public function getAction() {
        return $this->_action;
    }
}