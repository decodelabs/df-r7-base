<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

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
        $this->context = $context;
        $this->_manager = flow\Manager::getInstance();
    }

    public function getManager() {
        return $this->_manager;
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


// Flash shortcuts
    public function flashInfo($id, $message=null) {
        return $this->flash($id, $message, 'info');
    }

    public function flashSuccess($id, $message=null) {
        return $this->flash($id, $message, 'success');
    }

    public function flashWarning($id, $message=null) {
        return $this->flash($id, $message, 'warning');
    }

    public function flashError($id, $message=null) {
        return $this->flash($id, $message, 'error');
    }

    public function flashDebug($id, $message=null) {
        return $this->flash($id, $message, 'debug');
    }

    public function flashSaveSuccess($itemName, $message=null) {
        return $this->flash(
            $this->context->format->id($itemName).'.save',
            $message ?? $this->context->_('The %i% was successfully saved', ['%i%' => $itemName]),
            'success'
        );
    }


// Notifications
    public function notify($subject, $body, $to=null, $from=null, $forceSend=false) {
        return $this->sendNotification($this->newNotification($subject, $body, $to, $from));
    }

    public function adminNotify($subject, $body, $forceSend=false) {
        return $this->notify($subject, $body, true);
    }

    public function newNotification($subject, $body, $to=null, $from=null, $forceSend=false) {
        return $this->_manager->newNotification($subject, $body, $to, $from, $forceSend);
    }

    public function componentNotify($path, array $args=[], $to=null, $from=null, $preview=false, $forceSend=false) {
        return $this->sendNotification($this->newComponentNotification($path, $args, $to, $from, $preview, $forceSend));
    }

    public function componentAdminNotify($path, array $args=[], $preview=false, $forceSend=false) {
        return $this->componentNotify($path, $args, true, null, $preview, $forceSend);
    }

    public function newComponentNotification($path, array $args=[], $to=null, $from=null, $preview=false, $forceSend=false) {
        $component = $this->getMailComponent($path, $args);

        if($forceSend) {
            $component->shouldForceSend(true);
        }

        if($preview) {
            return $component->toPreviewNotification($to, $from);
        } else {
            return $component->toNotification($to, $from);
        }
    }

    public function getMailComponent($path, array $args=[]) {
        $parts = explode('/', $path);
        $name = array_pop($parts);
        $location = implode('/', $parts).'/';

        if(substr($location, 0, 1) != '~') {
            $location = '~mail/'.$location;
        }

        $context = arch\Context::factory($location);
        $component = arch\component\Base::factory($context, $name, $args);

        if(!$component instanceof arch\IMailComponent) {
            throw new arch\InvalidArgumentException(
                'Component mails can only use view components that support conversion to notifications'
            );
        }

        return $component;
    }


    public function sendNotification(flow\INotification $notification) {
        $this->_manager->sendNotification($notification);
        return $this;
    }


// Direct mail
    public function componentMail($path, array $args=[]) {
        $component = $this->getMailComponent($path, $args);
        $notification = $component->toNotification();

        $mail = new flow\mail\LegacyMessage();
        $mail->setSubject($notification->getSubject());
        $mail->setBodyHtml($notification->getBodyHtml());

        foreach($notification->getToEmails() as $email => $n) {
            $mail->addToAddress($email);
        }

        return $mail;
    }
}