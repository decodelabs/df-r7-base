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

use DecodeLabs\Tagged;
use DecodeLabs\Tagged\Buffer;
use DecodeLabs\Chirp\Parser as Chirp;
use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Metamorph;

class Html implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;

    public function __call($member, $args): aura\html\widget\IWidget
    {
        return aura\html\widget\Base::factory($this->context, $member, $args);
    }

    public function __invoke($name, $content=null, array $attributes=null)
    {
        return Tagged::el((string)$name, $content, $attributes);
    }

    public function previewText($html, $length=null)
    {
        $output = $this->toText($html);

        if ($output === null) {
            return null;
        }

        if ($length !== null) {
            $output = Dictum::shorten($output, $length);
        }

        return Tagged::raw($output);
    }

    public function toText($html)
    {
        if (is_string($html)) {
            $html = new aura\html\ElementString($html);
        }

        $html = aura\html\ElementContent::normalize($html);

        if (!strlen($html)) {
            return null;
        }

        $output = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $output = str_replace("\r\n", "\n", $output);
        return $output;
    }

    public function markdown($text)
    {
        if (!strlen($text)) {
            return null;
        }

        if (!class_exists(\Parsedown::class)) {
            throw Exceptional::Implementation(
                'Parsedown library is not available'
            );
        }

        $parser = new \Parsedown();
        //$parser->setSafeMode(true);
        $output = $parser->text($text);

        if ($output === null) {
            return null;
        }

        $output = preg_replace_callback('/ (href|src)\=\"([^\"]+)\"/', function ($matches) {
            return ' '.$matches[1].'="'.$this->context->uri->__invoke(html_entity_decode($matches[2])).'"';
        }, $output);

        $output = Tagged::raw($output);
        return $output;
    }

    public function simpleTags(?string $text, bool $extended=false)
    {
        $output = (new flex\simpleTags\Parser($text, $extended))->toHtml();

        if ($output !== null) {
            $output = Tagged::raw($output);
        }

        return $output;
    }

    public function inlineSimpleTags(?string $text)
    {
        $output = (new flex\simpleTags\Parser($text))->toInlineHtml();

        if ($output !== null) {
            $output = Tagged::raw($output);
        }

        return $output;
    }

    public function convert($body, $format='SimpleTags')
    {
        switch (strtolower($format)) {
            case 'simpletags':
                return $this->simpleTags($body);

            case 'inlinesimpletags':
                return $this->inlineSimpleTags($body);

            case 'tweet':
                return Metamorph::tweet($body);

            case 'plaintext':
                return Metamorph::text($body);

            case 'rawhtml':
            case 'html':
                return Tagged::raw($body);
        }
    }

    public function shorten($string, $length=20)
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        $newString = Dictum::shorten($string, $length);
        return Tagged::{'abbr'}($newString)->setTitle($string);
    }

    public function _($phrase='', $b=null, $c=null): aura\html\IElementRepresentation
    {
        return $this->translate(func_get_args());
    }

    public function translate(array $args): aura\html\IElementRepresentation
    {
        return new aura\html\ElementString($this->context->i18n->translate($args));
    }

    public function elementContentContainer($content=null): aura\html\IElementContent
    {
        return new aura\html\ElementContent($content);
    }

    public function autoField($key, $name, core\collection\ITree $values=null)
    {
        $isRequired = $isBoolean = false;

        if (substr($key, 0, 1) == '*') {
            $key = substr($key, 1);
            $isRequired = true;
        } elseif (substr($key, 0, 1) == '?') {
            $key = substr($key, 1);
            $isBoolean = true;
        }

        $value = null;

        if ($values) {
            $value = $values->{$key};
        }

        if ($isBoolean) {
            return $this->checkbox($key, $value, $name);
        } else {
            return $this->textbox($key, $value)
                ->isRequired($isRequired)
                ->setPlaceholder($name);
        }
    }



    // Compound widget shortcuts
    public function icon($name, $body=null)
    {
        if ($this->view) {
            $theme = $this->view->getTheme();
        } else {
            $theme = $this->context->apex->getTheme();
        }

        $iconChar = $theme->mapIcon($name);
        $attrs = [];

        if ($iconChar !== null) {
            $attrs = ['data-icon' => new aura\html\ElementString($iconChar)];
        }

        if (empty($body)) {
            $attrs['aria-hidden'] = 'true';
        }

        return new aura\html\Element('span', $body, $attrs);
    }

    public function booleanIcon($value, $body=null)
    {
        return $this->icon((bool)$value ? 'tick' : 'cross', $body)
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function yesNoIcon($value, $allowNull=true)
    {
        if ($value === null && $allowNull) {
            return null;
        }

        return $this->icon($value ? 'accept' : 'deny', $value ? 'Yes' : 'No')
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function lockIcon($value, $body=null)
    {
        return $this->icon((bool)$value ? 'lock' : 'unlock', $body)
            ->addClass((bool)$value ? 'locked' : 'unlocked');
    }


    public function basicLink($url, $body=null)
    {
        $url = $this->context->uri->__invoke($url);

        if (empty($body) && $body !== '0') {
            $body = $url;
        }

        return Tagged::{'a'}($body, ['href' => $url]);
    }

    public function plainMailLink($address, $body=null)
    {
        if (empty($address)) {
            return $body;
        }

        if (!$address = flow\mail\Address::factory($address)) {
            throw Exceptional::InvalidArgument(
                'Invalid email address'
            );
        }

        if ($body === null) {
            $body = $address->getName();

            if (empty($body)) {
                $body = $address->getAddress();
            }
        }

        return $this->link($this->context->uri->mailto($address), $body);
    }

    public function mailLink($address, $body=null)
    {
        if (empty($address)) {
            return $body;
        }

        return $this->plainMailLink($address, $body)
            ->setIcon('mail')
            ->setDisposition('external');
    }

    public function phoneLink($number, $icon='phone')
    {
        if (empty($number)) {
            return null;
        }

        return $this->link('tel:'.$number, $number)
            ->setIcon($icon);
    }

    public function backLink($default=null, $success=true, $body=null)
    {
        return $this->link(
                $this->context->uri->back($default, $success),
                $body ?? $this->context->_('Back')
            )
            ->setIcon('back');
    }

    public function queryToggleLink($request, $queryVar, $onString, $offString, $onIcon=null, $offIcon=null)
    {
        $result = false;
        $uriHelper = $this->context->uri;

        if (!$uriHelper instanceof Uri) {
            throw Exceptional::Runtime(
                'Bad helper!'
            );
        }

        return $this->link(
                $uriHelper->queryToggle($request, $queryVar, $result),
                $result ? $onString : $offString
            )
            ->setIcon($result ? $onIcon : $offIcon);
    }

    public function flashList()
    {
        $manager = flow\Manager::getInstance();
        $manager->processFlashQueue();
        $messageCount = 0;

        $isProduction = df\Launchpad::$app->isProduction();

        $output = '<div class="w list flash">'."\n";
        $change = false;

        foreach ($manager->getConstantFlashes() as $message) {
            $message->isDisplayed(true);
            $change = true;

            if ($isProduction && $message->isDebug()) {
                continue;
            }

            $messageCount++;
            $output .= $this->flashMessage($message);
        }

        foreach ($manager->getInstantFlashes() as $message) {
            $message->isDisplayed(true);
            $change = true;

            if ($isProduction && $message->isDebug()) {
                continue;
            }

            $messageCount++;
            $output .= $this->flashMessage($message);
        }

        if ($change) {
            $manager->flashHasChanged(true);
        }

        $output .= '</div>';

        if (!$messageCount) {
            return null;
        }

        return $output;
    }

    public function defaultButtonGroup($mainEvent=null, $mainEventText=null, $mainEventIcon=null)
    {
        return $this->buttonArea(
            $this->saveEventButton($mainEvent, $mainEventText, $mainEventIcon),

            $this->buttonGroup(
                $this->resetEventButton(),
                $this->cancelEventButton()
            )
        );
    }

    public function yesNoButtonGroup($mainEvent=null)
    {
        if (!$mainEvent) {
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

    public function saveEventButton($event=null, $text=null, $icon=null, $disposition=null)
    {
        if ($event === false) {
            return null;
        }

        if (!$event) {
            $event = 'save';
        }

        if ($text === null) {
            $text = $this->context->_('Save');
        }

        if ($icon === null) {
            $icon = 'save';
        }

        if ($disposition === null) {
            $disposition = 'positive';
        } elseif ($disposition === false) {
            $disposition = null;
        }

        return $this->eventButton($event, $text)
            ->setIcon($icon)
            ->setDisposition($disposition);
    }

    public function resetEventButton($event=null, $label=null, $icon=null, $disposition=null)
    {
        if ($event === false) {
            return null;
        }

        if (!$event) {
            $event = 'reset';
        }

        if ($label === null) {
            $label = $this->context->_('Reset');
        }

        if ($icon === null) {
            $icon = 'refresh';
        }

        return $this->eventButton($event, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
            ->shouldValidate(false);
    }

    public function cancelEventButton($event=null, $label=null, $icon=null, $disposition=null)
    {
        if ($event === false) {
            return null;
        }

        if (!$event) {
            $event = 'cancel';
        }

        if ($label === null) {
            $label = $this->context->_('Cancel');
        }

        if ($icon === null) {
            $icon = 'cancel';
        }

        if ($disposition === null) {
            $disposition = 'transitive';
        } elseif ($disposition === false) {
            $disposition = null;
        }

        return $this->eventButton($event, $label)
            ->setIcon($icon)
            ->setDisposition($disposition)
            ->shouldValidate(false);
    }

    public function jsonLd(string $type, $data, string $context=null): Buffer
    {
        if ($context === null) {
            $context = 'http://schema.org';
        }

        $tree = new core\collection\Tree([
            '@context' => $context,
            '@type' => $type
        ]);

        $tree->merge(core\collection\Tree::factory($data));
        $tree->removeEmpty();

        return Tagged::raw(
            Tagged::tag('script', ['type' => 'application/ld+json']).
            flex\Json::toString($tree, \JSON_UNESCAPED_SLASHES).
            '</script>')
        ;
    }

    public function breadcrumbsLd(arch\navigation\breadcrumbs\EntryList $breadcrumbs)
    {
        $data = [];
        $i = 0;

        foreach ($breadcrumbs->getEntries() as $link) {
            $data[] = [
                '@type' => 'ListItem',
                'position' => ++$i,
                'name' => $link->getBody(),
                'item' => $link->getUri()
            ];
        }

        return $this->jsonLd('BreadcrumbList', ['itemListElement' => $data]);
    }


    // Image
    public function image($url, $alt=null, $width=null, $height=null)
    {
        $output = Tagged::{'img'}(null, [
            'src' => $this->context->uri->__invoke($url),
            'alt' => $alt
        ]);

        if ($width !== null) {
            $output->setAttribute('width', $width);
        }

        if ($height !== null) {
            $output->setAttribute('height', $height);
        }

        return $output;
    }

    public function themeImage($path, $alt=null, $width=null, $height=null)
    {
        return $this->image($this->context->uri->themeAsset($path), $alt, $width, $height);
    }
}
