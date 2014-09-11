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
        $this->_manager = flow\Manager::getInstance();
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
    public function notify($subject, $body, $to=null, $from=null) {
        return $this->sendNotification($this->newNotification($subject, $body, $to, $from));
    }

    public function adminNotify($subject, $body) {
        return $this->notify($subject, $body, true);
    }

    public function newNotification($subject, $body, $to=null, $from=null) {
        return $this->_manager->newNotification($subject, $body, $to, $from);
    }



    public function templateNotify($path, $contextRequest, array $args=[], $to=null, $from=null) {
        return $this->sendNotification($this->newTemplateNotification($path, $contextRequest, $args, $to, $from));
    }

    public function templateAdminNotify($path, $contextRequest, array $args=[]) {
        return $this->templateNotify($path, $contextRequest, $args, true);
    }

    public function newTemplateNotification($path, $contextRequest, array $args=[], $to=null, $from=null) {
        if($this->_context instanceof arch\IContext) {
            $aura = $this->_context->aura;
        } else {
            $aura = arch\Context::factory()->aura;
        }

        $view = $aura->getView($path, $contextRequest);

        if(!$view instanceof aura\view\INotificationProxyView) {
            throw new aura\view\InvalidArgumentException(
                'Templated notifications can only use view templates that support conversion to notifications'
            );
        }

        $view->setArgs($args);
        return $view->toNotification($to, $from);
    }


    public function componentNotify($path, array $args=[], $to=null, $from=null, $preview=false) {
        return $this->sendNotification($this->newComponentNotification($path, $args, $to, $from, $preview));
    }

    public function componentAdminNotify($path, array $args=[], $preview=false) {
        return $this->componentNotify($path, $args, true, null, $preview);
    }

    public function newComponentNotification($path, array $args=[], $to=null, $from=null, $preview=false) {
        $component = $this->getMailComponent($path, $args);

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

        $mail = new flow\mail\Message();
        $mail->setSubject($notification->getSubject());
        $mail->setBodyHtml($notification->getBodyHtml());
        $mail->isPrivate($notification->isPrivate());

        foreach($notification->getToEmails() as $email => $n) {
            $mail->addToAddress($email);
        }

        return $mail;
    }
}