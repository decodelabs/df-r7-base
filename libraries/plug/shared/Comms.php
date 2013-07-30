<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\plug;
use df\arch;
    
class Comms implements core\ISharedHelper {

    use core\TSharedHelper;

    public function getFlashManager() {
        return arch\flash\Manager::getInstance($this->_context->application);
    }

    public function flash($id, $message=null, $type=null) {
        $manager = $this->getFlashManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->queueMessage($message);

        return $message;
    }

    public function flashNow($id, $message=null, $type=null) {
        $manager = $this->getFlashManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setInstantMessage($message);

        return $message;
    }

    public function flashAlways($id, $message=null, $type=null) {
        $manager = $this->getFlashManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setConstantMessage($message);

        return $message;
    }

    public function removeConstantFlash($id) {
        $manager = $this->getFlashManager();
        $manager->removeConstantMessage($id);

        return $this;
    }

    public function removeQueuedFlash($id) {
        $manager = $this->getFlashManager();
        $manager->removeQueuedMessage($id);

        return $this;
    }
}