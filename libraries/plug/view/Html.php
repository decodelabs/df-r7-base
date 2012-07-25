<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;
use df\arch;

class Html implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
    public function __call($member, $args) {
        return aura\html\widget\Base::factory($this->_view->getContext(), $member, $args)->setRenderTarget($this->_view);
    }
    
    public function string($value) {
        return new aura\html\ElementString($value);
    }
    
    public function tag($name, array $attributes=array()) {
        return new aura\html\Tag($name, $attributes);
    }
    
    public function element($name, $content=null, array $attributes=array()) {
        return new aura\html\Element($name, $content, $attributes);
    }



// Compound widget shortcuts
    public function icon($name, $body=null) {
        $iconChar = $this->_view->getTheme()->mapIcon($name);

        if($iconChar === null) {
            return null;
        }

        return new aura\html\Element('span', $body, [
            'aria-hidden' => 'true',
            'data-icon' => new aura\html\ElementString($iconChar)
        ]);
    }

    public function booleanIcon($value, $body=null) {
        return $this->icon((bool)$value ? 'tick' : 'cross', $body)
            ->addClass((bool)$value ? 'disposition-positive' : 'disposition-negative');
    }

    public function backLink($default=null, $success=true) {
        return $this->link(
                $this->_view->uri->back($default, $success),
                $this->_view->_('Back')
            )
            ->setIcon('back');
    }

    public function notificationList() {
        try {
            $manager = arch\notify\Manager::getInstance($this->_view->getContext()->getApplication());
            $messageCount = 0;


            if(!$manager->isFlushed()) {
                $manager->flushQueue();
            }

            $isProduction = df\Launchpad::$environmentMode == 'production';

            $output = '<section class="widget-notificationList">'."\n";

            foreach($manager->getConstantMessages() as $message) {
                $message->isDisplayed(true);

                if($isProduction && $message->isDebug()) {
                    continue;
                }

                $messageCount++;
                $output .= $this->notification($message);
            }

            foreach($manager->getInstantMessages() as $message) {
                $message->isDisplayed(true);

                if($isProduction && $message->isDebug()) {
                    continue;
                }

                $messageCount++;
                $output .= $this->notification($message);
                
            }

            $output .= '</section>';

            if(!$messageCount) {
                return null;
            }

            return $output;
        } catch(\Exception $e) {
            return new aura\view\content\ErrorContainer($this->_view, $e);
        }
    }

    public function defaultButtonGroup($mainAction=null) {
        if(!$mainAction) {
            $mainAction = 'save';
        }

        return $this->buttonArea(
            $this->saveEventButton($mainAction),
            $this->resetEventButton(),
            $this->cancelEventButton()
        );
    }

    public function yesNoButtonGroup($mainAction=null) {
        if(!$mainAction) {
            $mainAction = 'submit';
        }

        return $this->buttonArea(
            $this->eventButton($mainAction, $this->_view->_('Yes'))
                ->setIcon('accept'),

            $this->eventButton('cancel', $this->_view->_('No'))
                ->setIcon('deny')
                ->shouldValidate(false)
        );
    }

    public function saveEventButton($mainAction=null) {
        if(!$mainAction) {
            $mainAction = 'save';
        }

        return $this->eventButton($mainAction, $this->_view->_('Save'))
            ->setIcon('save');
    }

    public function resetEventButton() {
        return $this->eventButton('reset', $this->_view->_('Reset'))
            ->setIcon('refresh')
            ->shouldValidate(false);
    }

    public function cancelEventButton() {
        return $this->eventButton('cancel', $this->_view->_('Cancel'))
            ->setIcon('cancel')
            ->shouldValidate(false);
    }
}
