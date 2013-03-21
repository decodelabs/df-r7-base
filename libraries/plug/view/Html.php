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

class Html implements aura\view\IHelper, core\i18n\translate\ITranslationProxy {
    
    use aura\view\THelper;
    
    public function __call($member, $args) {
        return aura\html\widget\Base::factory($this->_view->getContext(), $member, $args)->setRenderTarget($this->_view);
    }
    
    public function plainText($text) {
        if(empty($text) && $text !== '0') {
            return null;
        }

        $text = $this->_view->esc($text);
        $text = str_replace("\n", "\n".'<br />', $text);

        return $this->string($text);
    }

    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->string($this->_view->_($phrase, $data, $plural, $locale));
    }

    public function string($value) {
        return new aura\html\ElementString(implode('', func_get_args()));
    }
    
    public function tag($name, array $attributes=array()) {
        return new aura\html\Tag($name, $attributes);
    }
    
    public function element($name, $content=null, array $attributes=array()) {
        return new aura\html\Element($name, $content, $attributes);
    }

    public function elementContentContainer($content=null) {
        return new aura\html\ElementContent($content);
    }

    public function span($content, array $attributes=array()) {
        return $this->element('span', $content, $attributes);
    }



// Compound widget shortcuts
    public function icon($name, $body=null) {
        $iconChar = $this->_view->getTheme()->mapIcon($name);
        $attrs = array();

        if($iconChar !== null) {
            $attrs = ['data-icon' => new aura\html\ElementString($iconChar)];
        }

        if(empty($body)) {
            $attrs['aria-hidden'] = 'true';
        }

        return new aura\html\Element('span', $body, $attrs);
    }

    public function booleanIcon($value, $body=null) {
        return $this->icon((bool)$value ? 'tick' : 'cross', $body)
            ->addClass((bool)$value ? 'disposition-positive' : 'disposition-negative');
    }

    public function lockIcon($value, $body=null) {
        return $this->icon((bool)$value ? 'lock' : 'unlock', $body)
            ->addClass((bool)$value ? 'state-locked' : 'state-unlocked');
    }

    public function backLink($default=null, $success=true) {
        return $this->link(
                $this->_view->uri->back($default, $success),
                $this->_view->_('Back')
            )
            ->setIcon('back');
    }

    public function queryToggleLink($request, $queryVar, $onString, $offString, $onIcon=null, $offIcon=null) {
        return $this->link(
                $this->_view->uri->queryToggle($request, $queryVar, $result),
                $result ? $onString : $offString
            )
            ->setIcon($result ? $onIcon : $offIcon);
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

    public function defaultButtonGroup($mainAction=null, $mainActionText=null) {
        return $this->buttonArea(
            $this->saveEventButton($mainAction, $mainActionText),
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

    public function saveEventButton($mainAction=null, $mainActionText=null) {
        if(!$mainAction) {
            $mainAction = 'save';
        }

        if(!$mainActionText) {
            $mainActionText = $this->_view->_('Save');
        }

        return $this->eventButton($mainAction, $mainActionText)
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


// Date
    public function date($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('Y-m-d'), 
            $this->_view->format->date($date, $size, $locale)
        );
    }
    
    public function userDate($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('Y-m-d'), 
            $this->_view->format->userDate($date, $size)
        );
    }
    
    public function dateTime($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->dateTime($date, $size, $locale)
        );
    }
    
    public function userDateTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->userDateTime($date, $size)
        );
    }

    public function customDate($date, $format) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->customDate($date, $format)
        );
    }
    
    public function time($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('H:m:s'), 
            $this->_view->format->time($date, $size, $locale)
        );
    }
    
    public function userTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('H:m:s'), 
            $this->_view->format->userTime($date, $size)
        );
    }
    
    
    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->timeSince($date, $locale, $maxUnits, $shortUnits, $maxUnit)
        );
    }
    
    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $locale=true) {
        if($date === null) {
            return null;
        }
        
        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->timeUntil($date, $locale, $maxUnits, $shortUnits, $maxUnit)
        );
    }

    protected function _timeTag($w3cString, $formattedString) {
        return $this->element(
            'time', 
            $formattedString,
            ['datetime' => $w3cString]
        );
    }


// Image
    public function image($url, $alt=null) {
        return $this->element(
            'img', null, [
                'src' => $this->_view->uri->to($url),
                'alt' => $alt
            ]
        );
    }

    public function themeImage($path, $alt=null) {
        return $this->element(
            'img', null, [
                'src' => $this->_view->uri->themeAsset($path),
                'alt' => $alt
            ]
        );
    }
}
