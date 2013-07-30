<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\plug;
use df\flow;
    
class Comms implements core\ISharedHelper {

    use core\TSharedHelper;

    protected $_manager;

    public function __construct(core\IContext $context) {
        $this->_context = $context;
        $this->_manager = flow\Manager::getInstance($this->_context->application);
    }


// Flash messages
    public function flash($id, $message=null, $type=null) {
        return $this->_manager->flash($id, $message, $type);
    }

    public function flashNow($id, $message=null, $type=null) {
        return $this->_manager->flashNow($id, $message, $type);
    }

    public function flashAlways($id, $message=null, $type=null) {
        return $this->_manager->flashAlways($id, $message, $type);
    }

    public function removeConstantFlash($id) {
        $this->_manager->removeConstantFlash($id);
        return $this;
    }

    public function removeQueuedFlash($id) {
        $this->_manager->removeQueuedFlash($id);
        return $this;
    }


// Notifications
    public function newNotification() {

    }

    public function sendNotification() {

    }
}