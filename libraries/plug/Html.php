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
    use aura\view\TView_DirectoryHelper;
    use flex\THtmlStringEscapeHandler;

    public function __call($member, $args) {
        return aura\html\widget\Base::factory($this->context, $member, $args);
    }

    public function __invoke($name, $content=null, array $attributes=[]) {
        if(false !== strpos($name, '>')) {
            $parts = explode('>', $name);

            foreach(array_reverse($parts) as $name) {
                $content = new aura\html\Element(trim($name), $content, $attributes);
            }

            return $content;
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
        if(is_string($html)) {
            $html = new aura\html\ElementString($html);
        }

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

    public function markdown($text) {
        $output = (new flex\markdown\Parser($text))->toHtml();

        if($output !== null) {
            $output = $this->string($output);
        }

        return $output;
    }

    public function simpleTags(string $text, bool $extended=false) {
        $output = (new flex\simpleTags\Parser($text, $extended))->toHtml();

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

        $newString = flex\Text::shorten($string, $length);
        return $this->element('abbr', $newString)->setTitle($string);
    }

    public function _($phrase=''): aura\html\IElementRepresentation {
        return $this->translate(func_get_args());
    }

    public function translate(array $args): aura\html\IElementRepresentation {
        return new aura\html\ElementString($this->context->i18n->translate($args));
    }

    public function string(...$values) {
        return new aura\html\ElementString(implode('', $values));
    }

    public function tag($name, array $attributes=[]) {
        return new aura\html\Tag($name, $attributes);
    }

    public function element($name, $content=null, array $attributes=[]): aura\html\IElement {
        return new aura\html\Element($name, $content, $attributes);
    }

    public function elementContentContainer($content=null): aura\html\IElementContent {
        return new aura\html\ElementContent($content);
    }

    public function list(iterable $list, string $container, string $name, callable $callback, array $attributes=[]): aura\html\IElementRepresentation {
        return new aura\html\Element($container, function() use($list, $name, $callback) {
            $i = 0;

            foreach($list as $key => $item) {
                yield $this->__invoke($name, function($el) use($key, $item, $callback, &$i) {
                    return $callback($item, $el, $key, ++$i);
                });
            }
        }, $attributes);
    }

    public function elements(iterable $list, string $name, callable $callback, array $attributes=[]): aura\html\IElementRepresentation {
        return aura\html\ElementContent::normalize(function() use($list, $name, $callback, $attributes) {
            $i = 0;

            foreach($list as $key => $item) {
                yield $this->__invoke($name, function($el) use($key, $item, $callback, &$i) {
                    return $callback($item, $el, $key, ++$i);
                }, $attributes);
            }
        });
    }

    public function uList(iterable $list, callable $renderer=null, array $attributes=[]): aura\html\IElementRepresentation {
        return $this->list($list, 'ul', 'li', $renderer ?? function($value) {
            return $value;
        }, $attributes);
    }

    public function oList(iterable $list, callable $renderer=null, array $attributes=[]): aura\html\IElementRepresentation {
        return $this->list($list, 'ol', 'li', $renderer ?? function($value) {
            return $value;
        }, $attributes);
    }

    public function span($content, array $attributes=[]) {
        return $this->element('span', $content, $attributes);
    }




    public function autoField($key, $name, core\collection\ITree $values=null) {
        $isRequired = $isBoolean = false;

        if(substr($key, 0, 1) == '*') {
            $key = substr($key, 1);
            $isRequired = true;
        } else if(substr($key, 0, 1) == '?') {
            $key = substr($key, 1);
            $isBoolean = true;
        }

        $value = null;

        if($values) {
            $value = $values->{$key};
        }

        if($isBoolean) {
            return $this->checkbox($key, $value, $name);
        } else {
            return $this->textbox($key, $value)
                ->isRequired($isRequired)
                ->setPlaceholder($name);
        }
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

    public function yesNoIcon($value, $allowNull=true) {
        if($value === null && $allowNull) {
            return null;
        }

        return $this->icon($value ? 'accept' : 'deny', $value ? 'Yes' : 'No')
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function lockIcon($value, $body=null) {
        return $this->icon((bool)$value ? 'lock' : 'unlock', $body)
            ->addClass((bool)$value ? 'locked' : 'unlocked');
    }


    public function diff($diff, $invert=false, $tag='sup') {
        if($diff > 0) {
            $arrow = '⬆';
        } else if($diff < 0) {
            $arrow = '⬇';
        } else {
            $arrow = '⬌';
        }

        $output = $this($tag, [
            $arrow,
            $this->number(abs($diff))
        ])->addClass('w-diff');

        if($invert !== null) {
            if($invert) $diff *= -1;
            $output->addClass($diff < 0 ? 'negative' : 'positive');
        }

        return $output;
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

    public function phoneLink($number, $icon='phone') {
        if(empty($number)) {
            return null;
        }

        return $this->link('tel:'.$number, $number)
            ->setIcon($icon);
    }

    public function backLink($default=null, $success=true, $body=null) {
        return $this->link(
                $this->context->uri->back($default, $success),
                $body ?? $this->context->_('Back')
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

        $output = '<section class="w-flashList">'."\n";

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

    public function defaultButtonGroup($mainEvent=null, $mainEventText=null, $mainEventIcon=null) {
        return $this->buttonArea(
            $this->saveEventButton($mainEvent, $mainEventText, $mainEventIcon),

            $this->buttonGroup(
                $this->resetEventButton(),
                $this->cancelEventButton()
            )
        );
    }

    public function yesNoButtonGroup($mainEvent=null) {
        if(!$mainEvent) {
            $mainEvent = 'submit';
        }

        return $this->buttonArea(
            $this->eventButton($mainEvent, $this->context->_('Yes'))
                ->setIcon('accept'),

            $this->eventButton('cancel', $this->context->_('No'))
                ->setIcon('deny')
                ->shouldValidate(false)
        );
    }

    public function saveEventButton($event=null, $text=null, $icon=null, $disposition=null) {
        if($event === false) {
            return null;
        }

        if(!$event) {
            $event = 'save';
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

        return $this->eventButton($event, $text)
            ->setIcon($icon)
            ->setDisposition($disposition);
    }

    public function resetEventButton($event=null, $label=null, $icon=null, $disposition=null) {
        if($event === false) {
            return null;
        }

        if(!$event) {
            $event = 'reset';
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

        return $this->eventButton($event, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
            ->shouldValidate(false);
    }

    public function cancelEventButton($event=null, $label=null, $icon=null, $disposition=null) {
        if($event === false) {
            return null;
        }

        if(!$event) {
            $event = 'cancel';
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

        return $this->eventButton($event, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
            ->shouldValidate(false);
    }



    public function number($value, $unit=null) {
        if($value === null) {
            return null;
        }

        if($unit === null && false !== strpos($value, ' ')) {
            list($value, $unit) = explode(' ', $value, 2);
        }

        return $this->element('span.numeric', function() use($value, $unit) {
            if(is_int($value)
            || is_float($value)
            || is_string($value) && (string)((float)$value) === $value) {
                $value = $this->context->format->number($value);
            }

            yield $this->element('span.value', $value);

            if($unit !== null) {
                yield $this->element('span.unit', $unit);
            }
        });
    }


// Date
    public function date($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, false)) {
            return null;
        }

        return $this->_timeTag(
            $date->format('Y-m-d'),
            $this->context->format->date($date, $size, null, $locale)
        );
    }

    public function dateTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        return $this->_timeTag(
            $date->format(core\time\Date::W3C),
            $this->context->format->dateTime($date, $size, null, $locale)
        );
    }

    public function customDate($date, string $format, $timezone=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        return $this->_timeTag(
            $date->hasTime() ?
                $date->format(core\time\Date::W3C) :
                $date->format('Y-m-d'),
            $this->context->format->customDate($date, $format, null)
        );
    }

    public function wrappedDate($date, $body, $timezone=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        return $this->_timeTag(
            $date->hasTime() ?
                $date->format(core\time\Date::W3C) :
                $date->format('Y-m-d'),
            $body
        );
    }

    public function time($date, $format=null, $timezone=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        if($format === null) {
            $format = 'g:ia';
        }

        return $this->_timeTag(
            $date->format('H:m:s'),
            $this->context->format->time($date, $format, null)
        );
    }

    public function localeTime($date, $size=core\time\Date::MEDIUM, $timezone=true, $locale=true) {
        if(!$date = $this->_prepareDate($date, $timezone, true)) {
            return null;
        }

        return $this->_timeTag(
            $date->format('H:m:s'),
            $this->context->format->time($date, $size, null, $locale)
        );
    }


    public function timeSince($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        return $this->_timeTag(
                $date->format(core\time\Date::W3C),
                $this->context->format->timeSince($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
            )
            ->setTitle($this->context->format->dateTime($date));
    }

    public function timeUntil($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=true) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        return $this->_timeTag(
                $date->format(core\time\Date::W3C),
                $this->context->format->timeUntil($date, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit, $locale)
            )
            ->setTitle($this->context->format->dateTime($date));
    }

    public function timeFromNow($date, $maxUnits=1, $shortUnits=false, $maxUnit=core\time\Duration::YEARS, $roundLastUnit=true, $locale=null) {
        if(!$date = core\time\Date::normalize($date)) {
            return null;
        }

        if($locale === null) {
            $locale = $this->context->getLocale();
        }

        if($locale === null) {
            $locale = true;
        }

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

    protected function _prepareDate($date, $timezone=true, bool $includeTime=true) {
        if($date instanceof core\time\ITimeOfDay) {
            return new core\time\Date($date);
        }

        if(!$date = core\time\Date::normalize($date, null, $includeTime)) {
            return null;
        }

        if($timezone !== null) {
            $date = clone $date;

            if($date->hasTime()) {
                if($timezone === true) {
                    $date->toUserTimeZone();
                } else {
                    $date->toTimezone($timezone);
                }
            }
        }

        return $date;
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

// Media
    public function videoEmbed($embed, $width=null, $height=null) {
        $embed = trim($embed);

        if(!empty($embed)) {
            return spur\video\Embed::parse($embed)
                ->setDimensions($width, $height);
        } else {
            return '';
        }
    }

    public function audioEmbed($embed, $width=null, $height=null) {
        $embed = trim($embed);

        if(!empty($embed)) {
            return spur\audio\Embed::parse($embed)
                ->setDimensions($width, $height);
        } else {
            return '';
        }
    }
}
