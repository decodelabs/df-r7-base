<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flex;
use df\spur;
use df\flow;

class Html implements arch\IDirectoryHelper {
    
    use arch\TDirectoryHelper;
    use aura\view\TViewAwareDirectoryHelper;
    use core\TTranslator;
    use core\string\THtmlStringEscapeHandler;
    
    public function __call($member, $args) {
        $output = aura\html\widget\Base::factory($this->context, $member, $args);

        if($this->view) {
            $output->setRenderTarget($this->view);
        }

        return $output;
    }

    public function __invoke($name, $content=null, array $attributes=[]) {
        if($content === null && empty($attributes) && (preg_match('/[^a-zA-Z0-9.#\_\-]/', $name) || !strlen($name))) {
            return new aura\html\ElementString($name);
        }

        return new aura\html\Element($name, $content, $attributes);
    }

    public function previewText($html, $length=null) {
        $html = strip_tags($html);
        $html = aura\html\ElementContent::normalize($html);

        if(!strlen($html)) {
            return null;
        }

        if($length !== null) {
            $html = $this->context->format->shorten($html, $length);
        }

        return $this->string($html);
    }

    public function toText($html) {
        $html = aura\html\ElementContent::normalize($html);

        if(!strlen($html)) {
            return null;
        }

        $output = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $output = str_replace("\r\n", "\n", $output);
        return $output;
    }

    public function plainText($text) {
        if(empty($text) && $text !== '0') {
            return null;
        }

        $text = $this->esc($text);
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

    public function tweet($text) {
        $output = (new flex\tweet\Parser($text))->toHtml();

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

            case 'tweet':
                return $this->tweet($body);

            case 'plaintext':
                return $this->plainText($body);

            case 'rawhtml':
            case 'html':
                return $this->string($value);
        }
    }

    public function shorten($string, $length=20) {
        if(strlen($string) <= $length) {
            return $string;
        }

        $newString = core\string\Manipulator::shorten($string, $length);
        return $this->element('abbr', $newString)->setTitle($string);
    }

    public function translate(array $args) {
        return new aura\html\ElementString($this->context->i18n->translate($args));
    }

    public function string($value) {
        return new aura\html\ElementString(implode('', func_get_args()));
    }
    
    public function tag($name, array $attributes=[]) {
        return new aura\html\Tag($name, $attributes);
    }
    
    public function element($name, $content=null, array $attributes=[]) {
        return new aura\html\Element($name, $content, $attributes);
    }

    public function elementContentContainer($content=null) {
        return new aura\html\ElementContent($content);
    }

    public function span($content, array $attributes=[]) {
        return $this->element('span', $content, $attributes);
    }



// Compound widget shortcuts
    public function icon($name, $body=null) {
        if($this->view) {
            $theme = $this->view->getTheme();
        } else {
            $theme = $this->context->apex->getTheme();
        }

        $iconChar = $theme->mapIcon($name);
        $attrs = [];

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
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function lockIcon($value, $body=null) {
        return $this->icon((bool)$value ? 'lock' : 'unlock', $body)
            ->addClass((bool)$value ? 'locked' : 'unlocked');
    }
    
    public function basicLink($url, $body=null) {
        $url = $this->context->uri->__invoke($url);

        if(empty($body) && $body !== '0') {
            $body = $url;
        }
        
        return $this->element('a', $body, ['href' => $url]);
    }

    public function plainMailLink($address, $body=null) {
        if(empty($address)) {
            return $body;
        }

        $address = flow\mail\Address::factory($address);

        if($body === null) {
            $body = $address->getName();

            if(empty($body)) {
                $body = $address->getAddress();
            }
        }

        return $this->link($this->context->uri->mailto($address), $body);
    }

    public function mailLink($address, $body=null) {
        if(empty($address)) {
            return $body;
        }
        
        return $this->plainMailLink($address, $body)
            ->setIcon('mail')
            ->setDisposition('external');
    }

    public function backLink($default=null, $success=true, $body=null) {
        return $this->link(
                $this->context->uri->back($default, $success),
                $body !== null ? $body : $this->context->_('Back')
            )
            ->setIcon('back');
    }

    public function queryToggleLink($request, $queryVar, $onString, $offString, $onIcon=null, $offIcon=null) {
        return $this->link(
                $this->context->uri->queryToggle($request, $queryVar, $result),
                $result ? $onString : $offString
            )
            ->setIcon($result ? $onIcon : $offIcon);
    }

    public function flashList() {
        $manager = flow\Manager::getInstance();
        $manager->processFlashQueue();
        $messageCount = 0;

        $isProduction = df\Launchpad::$application->isProduction();

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
    }

    public function defaultButtonGroup($mainAction=null, $mainActionText=null, $mainActionIcon=null) {
        return $this->buttonArea(
            $this->saveEventButton($mainAction, $mainActionText, $mainActionIcon),

            $this->buttonGroup(
                $this->resetEventButton(),
                $this->cancelEventButton()
            )
        );
    }

    public function yesNoButtonGroup($mainAction=null) {
        if(!$mainAction) {
            $mainAction = 'submit';
        }

        return $this->buttonArea(
            $this->eventButton($mainAction, $this->context->_('Yes'))
                ->setIcon('accept'),

            $this->eventButton('cancel', $this->context->_('No'))
                ->setIcon('deny')
                ->shouldValidate(false)
        );
    }

    public function saveEventButton($action=null, $text=null, $icon=null, $disposition=null) {
        if($action === false) {
            return null;
        }

        if(!$action) {
            $action = 'save';
        }

        if($text === null) {
            $text = $this->context->_('Save');
        }

        if($icon === null) {
            $icon = 'save';
        }

        if($disposition === null) {
            $disposition = 'positive';
        } else if($disposition === false) {
            $disposition = null;
        }

        return $this->eventButton($action, $text)
            ->setIcon($icon)
            ->setDisposition($disposition);
    }

    public function resetEventButton($action=null, $label=null, $icon=null, $disposition=null) {
        if($action === false) {
            return null;
        }

        if(!$action) {
            $action = 'reset';
        }

        if($label === null) {
            $label = $this->context->_('Reset');
        }

        if($icon === null) {
            $icon = 'refresh';
        }

        if($disposition === null) {
            $disposition = 'informative';
        } else if($disposition === false) {
            $disposition = null;
        }

        return $this->eventButton($action, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
            ->shouldValidate(false);
    }

    public function cancelEventButton($action=null, $label=null, $icon=null, $disposition=null) {
        if($action === false) {
            return null;
        }

        if(!$action) {
            $action = 'cancel';
        }

        if($label === null) {
            $label = $this->context->_('Cancel');
        }

        if($icon === null) {
            $icon = 'cancel';
        }

        if($disposition === null) {
            $disposition = 'transitive';
        } else if($disposition === false) {
            $disposition = null;
        }

        return $this->eventButton($action, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
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
            $this->context->format->date($date, $size, $locale)
        );
    }
    
    public function userDate($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('Y-m-d'), 
            $this->context->format->userDate($date, $size)
        );
    }
    
    public function dateTime($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->context->format->dateTime($date, $size, $locale)
        );
    }
    
    public function userDateTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->context->format->userDateTime($date, $size)
        );
    }

    public function customDate($date, $format, $userTime=false) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format(core\time\Date::W3C), 
            $this->context->format->customDate($date, $format, $userTime)
        );
    }
    
    public function time($date, $size=core\time\Date::MEDIUM, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('H:m:s'), 
            $this->context->format->time($date, $size, $locale)
        );
    }
    
    public function userTime($date, $size=core\time\Date::MEDIUM) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
            $date->format('H:m:s'), 
            $this->context->format->userTime($date, $size)
        );
    }
    
    
    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if($date === null) {
            return null;
        }

        $date = core\time\Date::factory($date);

        return $this->_timeTag(
                $date->format(core\time\Date::W3C), 
                $this->context->format->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
            )
            ->setTitle($this->context->format->dateTime($date));
    }
    
    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if($date === null) {
            return null;
        }
        
        $date = core\time\Date::factory($date);

        return $this->_timeTag(
                $date->format(core\time\Date::W3C), 
                $this->context->format->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
            )
            ->setTitle($this->context->format->dateTime($date));
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if($date === null) {
            return null;
        }
        
        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }

        $date = core\time\Date::factory($date);
        $ts = $date->toTimestamp();
        $now = core\time\Date::factory('now')->toTimestamp();
        $diff = $now - $ts;

        if($diff > 0) {
            $output = $this->context->_(
                '%t% ago',
                ['%t%' => $this->context->format->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else if($diff < 0) {
            $output = $this->context->_(
                'in %t%',
                ['%t%' => $this->context->format->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)]
            );
        } else {
            $output = $this->context->_('just now');
        }

        return $this->_timeTag(
                $date->format(core\time\Date::W3C), 
                $output
            )
            ->setTitle($this->context->format->dateTime($date));
    }

    protected function _timeTag($w3cString, $formattedString) {
        return $this->element(
            'time', 
            $formattedString,
            ['datetime' => $w3cString]
        );
    }


// Image
    public function image($url, $alt=null, $width=null, $height=null) {
        $output = $this->element(
            'img', null, [
                'src' => $this->context->uri->__invoke($url),
                'alt' => $alt
            ]
        );

        if($width !== null) {
            $output->setAttribute('width', $width);
        }

        if($height !== null) {
            $output->setAttribute('height',  $height);
        }

        return $output;
    }

    public function themeImage($path, $alt=null, $width=null, $height=null) {
        return $this->image($this->context->uri->themeAsset($path), $alt, $width, $height);
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
