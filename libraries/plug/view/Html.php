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
use df\flex;
use df\spur;
use df\flow;

class Html implements aura\view\IHelper, core\i18n\translate\ITranslationProxy {
    
    use aura\view\THelper;
    
    public function __call($member, $args) {
        return aura\html\widget\Base::factory($this->_view->getContext(), $member, $args)->setRenderTarget($this->_view);
    }
    
    public function previewText($html, $length=null) {
        $html = (string)$html;

        if(!strlen($html)) {
            return null;
        }

        $output = strip_tags($html);

        if($length !== null) {
            $output = $this->_view->format->shorten($output, $length);
        }

        return $this->string($output);
    }

    public function plainText($text) {
        if(empty($text) && $text !== '0') {
            return null;
        }

        $text = $this->_view->esc($text);
        $text = str_replace("\n", "\n".'<br />', $text);

        return $this->string($text);
    }

    public function simpleTags($text, array $customTags=null) {
        $output = (new flex\simpleTags\Parser($text, $customTags))->toHtml();

        if($output !== null) {
            $output = $this->string($output);
        }

        return $output;
    }

    public function inlineSimpleTags($text) {
        $output = (new flex\simpleTags\Parser($text))->toInlineHtml();

        if($output !== null) {
            $output = $this->string($output);
        }

        return $output;
    }

    public function convert($body, $format='SimpleTags') {
        switch(strtolower($format)) {
            case 'simpletags':
                return $this->simpleTags($body);

            case 'inlinesimpletags':
                return $this->inlineSimpleTags($body);

            case 'plaintext':
                return $this->plainText($body);

            case 'rawhtml':
            case 'html':
                return $this->string($value);
        }
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

    public function mailLink($address, $body=null) {
        if(empty($address)) {
            return null;
        }

        if($body === null) {
            $body = $address;
        }

        return $this->link($this->_view->uri->mailto($address), $body)
            ->setIcon('mail')
            ->setDisposition('transitive');
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

    public function flashList() {
        try {
            $application = $this->_view->getContext()->getApplication();
            $manager = flow\Manager::getInstance($application);
            $manager->processFlashQueue();
            $messageCount = 0;

            $isProduction = $application->isProduction();

            $output = '<section class="widget-flashList">'."\n";

            foreach($manager->getConstantFlashes() as $message) {
                $message->isDisplayed(true);

                if($isProduction && $message->isDebug()) {
                    continue;
                }

                $messageCount++;
                $output .= $this->flashMessage($message);
            }

            foreach($manager->getInstantFlashes() as $message) {
                $message->isDisplayed(true);

                if($isProduction && $message->isDebug()) {
                    continue;
                }

                $messageCount++;
                $output .= $this->flashMessage($message);
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
        if($mainAction === false) {
            return null;
        }

        if(!$mainAction) {
            $mainAction = 'save';
        }

        if(!$mainActionText) {
            $mainActionText = $this->_view->_('Save');
        }

        return $this->eventButton($mainAction, $mainActionText)
            ->setIcon('save');
    }

    public function resetEventButton($label=null) {
        if($label === null) {
            $label = $this->_view->_('Reset');
        }

        return $this->eventButton('reset', $label)
            ->setIcon('refresh')
            ->shouldValidate(false);
    }

    public function cancelEventButton($label=null) {
        if($label === null) {
            $label = $this->_view->_('Cancel');
        }

        return $this->eventButton('cancel', $label)
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
    
    
    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
        );
    }
    
    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if($date === null) {
            return null;
        }
        
        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->_view->format->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
        );
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        $date = core\time\Date::factory($date);

        if($date->isPast()) {
            $output = $this->_view->getContext()->_(
                '%t% ago',
                ['%t%' => $this->_view->format->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else {
            $output = $this->_view->getContext()->_(
                'in %t%',
                ['%t%' => $this->_view->format->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        }

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $output
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

// Video
    public function videoEmbed($embed, $width=null, $height=null) {
        $embed = trim($embed);

        if(!empty($embed)) {
            return spur\video\Embed::parse($embed)
                ->setDimensions($width, $height);
        } else {
            return '';
        }
    }
}
