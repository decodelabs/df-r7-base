<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flow;

class Notification extends Base implements INotificationProxyView {

    protected $_subject;

    public function setSubject($subject) {
        $this->_subject = $subject;
        return $this;
    }

    public function getSubject() {
        return $this->_subject;
    }

    public function toNotification($to=null, $from=null) {
        $content = $this->render();
        $subject = $this->_subject;

        if(empty($subject)) {
            $subject = $this->_('Notification from %a%', ['%a%' => $this->application->getName()]);
        }

        $manager = flow\Manager::getInstance();
        return $manager->newNotification($subject, $content, $to, $from)
            ->setBodyType(flow\INotification::SIMPLE_TAGS);
    }

    public function toHtml() {
        return $this->html->simpleTags($this->render());
    }
}