<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;
    
class Event implements IEvent {

    use core\collection\TArrayCollection_Map;

    protected $_action;
    protected $_entityLocator;
    protected $_entity;
    protected $_handler;

    public function __construct($action, array $data=null, $entity=null, $handler=null) {
        $this->setAction($action);

        if($data !== null) {
            $this->import($data);
        }

        if($entity !== null) {
            $this->setEntity($entity);
        }

        if($handler !== null) {
            $this->setHandler($handler);
        }
    }


// Entity
    public function setEntity($locator) {
        if($locator instanceof IEntity) {
            $this->_entity = $locator;
            $locator = $this->_entity->getEntityLocator();
        }

        if(!$locator instanceof IEntityLocator) {
            $locator = core\policy\entity\Locator::factory($locator);
        }

        $this->_entityLocator = $locator;
        return $this;
    }

    public function hasEntityLocator() {
        return $this->_entityLocator !== null;
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


// Handler
    public function setHandler($handler) {
        $this->_handler = $handler;
        return $this;
    }

    public function hasHandler() {
        return $this->_handler !== null;
    }

    public function getHandler() {
        return $this->_handler;
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