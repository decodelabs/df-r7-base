<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Metamorph;
use DecodeLabs\Tagged;
use DecodeLabs\Tagged\Markup;

use df\arch;
use df\aura;
use df\core;
use df\flex;
use df\flow;

class Html implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;
    use aura\view\TView_DirectoryHelper;

    public function __call($member, $args): aura\html\widget\IWidget
    {
        return aura\html\widget\Base::factory($this->context, $member, $args);
    }

    public function __invoke($name, $content = null, array $attributes = null)
    {
        return Tagged::el((string)$name, $content, $attributes);
    }

    public function convert($body, $format = 'SimpleTags')
    {
        switch (strtolower($format)) {
            case 'simpletags':
                return Metamorph::idiom($body);

            case 'inlinesimpletags':
                return Metamorph::idiom($body, [
                    'inline' => true
                ]);

            case 'tweet':
                return Metamorph::tweet($body);

            case 'plaintext':
                return Metamorph::text($body);

            case 'rawhtml':
            case 'html':
                return Tagged::raw($body);
        }
    }

    public function shorten($string, $length = 20)
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        $newString = Dictum::shorten($string, $length);
        return Tagged::{'abbr'}($newString)->setTitle($string);
    }

    public function _($phrase = '', $b = null, $c = null): aura\html\IElementRepresentation
    {
        return $this->translate(func_get_args());
    }

    public function translate(array $args): aura\html\IElementRepresentation
    {
        return new aura\html\ElementString($this->context->i18n->translate($args));
    }

    public function elementContentContainer($content = null): aura\html\IElementContent
    {
        return new aura\html\ElementContent($content);
    }

    public function autoField($key, $name, core\collection\ITree $values = null)
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
            /** @var aura\html\widget\Textbox $output */
            $output = $this->textbox($key, $value);
            $output
                ->isRequired($isRequired)
                ->setPlaceholder($name);
            return $output;
        }
    }



    // Compound widget shortcuts
    public function icon($name, $body = null)
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

    public function booleanIcon($value, $body = null)
    {
        return $this->icon((bool)$value ? 'tick' : 'cross', $body)
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function yesNoIcon($value, $allowNull = true)
    {
        if ($value === null && $allowNull) {
            return null;
        }

        return $this->icon($value ? 'accept' : 'deny', $value ? 'Yes' : 'No')
            ->addClass((bool)$value ? 'positive' : 'negative');
    }

    public function lockIcon($value, $body = null)
    {
        return $this->icon((bool)$value ? 'lock' : 'unlock', $body)
            ->addClass((bool)$value ? 'locked' : 'unlocked');
    }


    public function basicLink($url, $body = null)
    {
        $url = $this->context->uri->__invoke($url);

        if (empty($body) && $body !== '0') {
            $body = $url;
        }

        return Tagged::{'a'}($body, ['href' => $url]);
    }

    public function plainMailLink($address, $body = null)
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

    public function mailLink($address, $body = null)
    {
        if (empty($address)) {
            return $body;
        }

        return $this->plainMailLink($address, $body)
            ->setIcon('mail')
            ->setDisposition('external');
    }

    public function phoneLink($number, $icon = 'phone')
    {
        if (empty($number)) {
            return null;
        }

        /** @var aura\html\widget\Link $output */
        $output = $this->link('tel:' . $number, $number);
        $output->setIcon($icon);
        return $output;
    }

    public function backLink($default = null, $success = true, $body = null)
    {
        /** @var aura\html\widget\Link $output */
        $output = $this->link(
            $this->context->uri->back($default, $success),
            $body ?? $this->context->_('Back')
        );
        $output->setIcon('back');
        return $output;
    }

    public function queryToggleLink($request, $queryVar, $onString, $offString, $onIcon = null, $offIcon = null)
    {
        $result = false;
        $uriHelper = $this->context->uri;

        if (!$uriHelper instanceof Uri) {
            throw Exceptional::Runtime(
                'Bad helper!'
            );
        }

        /** @var aura\html\widget\Link $output */
        $output = $this->link(
            $uriHelper->queryToggle($request, $queryVar, $result),
            $result ? $onString : $offString
        );
        $output->setIcon($result ? $onIcon : $offIcon);
        return $output;
    }

    public function flashList(
        string|false|null $containerTag = null,
        ?string $messageTag = null,
    ): ?Markup {
        $output = function () use ($messageTag) {
            $manager = flow\Manager::getInstance();
            $manager->processFlashQueue();
            $messageCount = 0;
            $isProduction = Genesis::$environment->isProduction();

            $change = false;

            foreach (
                $manager->getConstantFlashes() +
                $manager->getInstantFlashes()
            as $message) {
                $message->isDisplayed(true);
                $change = true;

                if ($isProduction && $message->isDebug()) {
                    continue;
                }

                $messageCount++;

                /** @var \df\aura\html\widget\FlashMessage $flash */
                $flash = $this->flashMessage($message);

                if ($messageTag !== null) {
                    $flash
                        ->shouldShowIcon(false)
                        ->getTag()
                            ->setName($messageTag);
                }

                yield $flash;
            }

            if ($change) {
                $manager->flashHasChanged(true);
            }
        };


        if ($containerTag === false) {
            return Tagged::wrap($output);
        }

        if ($containerTag === null) {
            $containerTag = 'div.w.list.flash';
        }

        $containerTag = '?' . ltrim($containerTag, '?');

        return Tagged::{$containerTag}($output);
    }

    public function defaultButtonGroup($mainEvent = null, $mainEventText = null, $mainEventIcon = null)
    {
        return $this->buttonArea(
            $this->saveEventButton($mainEvent, $mainEventText, $mainEventIcon),
            $this->buttonGroup(
                $this->resetEventButton(),
                $this->cancelEventButton()
            )
        );
    }

    public function yesNoButtonGroup($mainEvent = null)
    {
        if (!$mainEvent) {
            $mainEvent = 'submit';
        }

        /** @var aura\html\widget\EventButton $yes */
        $yes = $this->eventButton($mainEvent, $this->context->_('Yes'));
        $yes->setIcon('accept');

        /** @var aura\html\widget\EventButton $no */
        $no = $this->eventButton('cancel', $this->context->_('No'));
        $no->setIcon('deny');
        $no->shouldValidate(false);

        return $this->buttonArea($yes, $no);
    }

    public function saveEventButton($event = null, $text = null, $icon = null, $disposition = null)
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

        /** @var aura\html\widget\EventButton $output */
        $output = $this->eventButton($event, $text);
        $output->setIcon($icon);
        $output->setDisposition($disposition);
        return $output;
    }

    public function resetEventButton($event = null, $label = null, $icon = null, $disposition = null)
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

        /** @var aura\html\widget\EventButton $output */
        $output = $this->eventButton($event, $label);
        $output->setIcon($icon);
        $output->setDisposition($disposition);
        $output->shouldValidate(false);
        return $output;
    }

    public function cancelEventButton($event = null, $label = null, $icon = null, $disposition = null)
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

        /** @var aura\html\widget\EventButton $output */
        $output = $this->eventButton($event, $label);
        $output->setIcon($icon);
        $output->setDisposition($disposition);
        $output->shouldValidate(false);
        return $output;
    }

    public function jsonLd(string $type, $data, string $context = null): void
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

        if (!$this->view) {
            throw Exceptional::Runtime('No view available', null, $tree);
        }

        $data = flex\Json::toString($tree, \JSON_UNESCAPED_SLASHES);

        $this->view->addHeadScript(
            'jsonLd-' . md5($data),
            $data,
            ['type' => 'application/ld+json']
        );
    }

    public function breadcrumbsLd(arch\navigation\breadcrumbs\EntryList $breadcrumbs): void
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

        $this->jsonLd('BreadcrumbList', ['itemListElement' => $data]);
    }


    // Image
    public function image($url, $alt = null, $width = null, $height = null)
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

    public function themeImage($path, $alt = null, $width = null, $height = null)
    {
        return $this->image($this->context->uri->themeAsset($path), $alt, $width, $height);
    }
}
