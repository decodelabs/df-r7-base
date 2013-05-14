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

    public function getNotificationManager() {
        return arch\notify\Manager::getInstance($this->application);
    }

    public function notify($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->queueMessage($message);

        return $message;
    }

    public function notifyNow($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setInstantMessage($message);

        return $message;
    }

    public function notifyAlways($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setConstantMessage($message);

        return $message;
    }

    public function removeConstantNotification($id) {
        $manager = $this->getNotificationManager();
        $manager->removeConstantMessage($id);

        return $this;
    }
}