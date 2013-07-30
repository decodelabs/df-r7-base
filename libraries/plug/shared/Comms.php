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
use df\arch;
use df\aura;
    
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
    public function notify($subject, $body, $to=null) {
        return $this->sendNotification($this->newNotification($subject, $body, $to));
    }

    public function templateNotify($path, $contextRequest, array $args=array(), $to=null, $from=null) {
        return $this->sendNotification($this->newTemplateNotification($path, $contextRequest, $args, $to, $from));
    }

    public function newNotification($subject, $body, $to=null, $from=null) {
        return $this->_manager->newNotification($subject, $body, $to, $from);
    }

    public function newTemplateNotification($path, $contextRequest, array $args=array(), $to=null, $from=null) {
        if($this->_context instanceof arch\IContext) {
            $aura = $this->_context->aura;
        } else {
            $aura = arch\Context::factory($this->_context->getApplication())->aura;
        }

        $view = $aura->getView($path, $contextRequest);

        if(!$view instanceof aura\view\Notification) {
            throw new aura\view\InvalidArgumentException(
                'Templated notifications can only use .notification templates'
            );
        }

        $view->setArgs($args);
        return $view->toNotification($to, $from);
    }

    public function sendNotification(flow\INotification $notification) {
        $this->_manager->sendNotification($notification);
        return $this;
    }
}