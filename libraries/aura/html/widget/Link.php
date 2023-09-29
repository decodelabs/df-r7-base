<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;
use df\aura;
use df\core;

use df\link as linkLib;

class Link extends Base implements
    ILinkWidget,
    IDescriptionAwareLinkWidget,
    IIconProviderWidget,
    Dumpable
{
    use TWidget_BodyContentAware;
    use core\constraint\TDisableable;
    use TWidget_TargetAware;
    use TWidget_DispositionAware;
    use TWidget_IconProvider;
    use arch\navigation\TSharedLinkComponents;

    public const PRIMARY_TAG = 'a';
    public const DEFAULT_ACTIVE_CLASS = 'active';

    protected $_rel = [];
    protected $_isActive = false;
    protected $_isComputedActive = null;
    protected $_matchExact = false;
    protected $_hrefLang;
    protected $_media;
    protected $_contentType;
    protected $_activeClass;

    public function __construct(arch\IContext $context, $uri, $body = null, $matchRequest = null)
    {
        parent::__construct($context);

        $checkUriMatch = false;
        $this->_checkAccess = null;

        if (is_bool($matchRequest)) {
            $checkUriMatch = true;
            $this->_matchExact = $matchRequest;
            $matchRequest = null;
        }

        if ($uri instanceof arch\navigation\entry\Link) {
            $link = $uri;
            $uri = $link->getUri();
            $body = $link->getBody();

            if ($icon = $link->getIcon()) {
                $this->_icon = $icon;
            }

            if ($description = $link->getDescription()) {
                $this->setDescription($description);
            }

            if (null !== ($note = $link->getNote())) {
                $this->setNote($note);
            }

            if ($disposition = $link->getDisposition()) {
                $this->setDisposition($disposition);
            }

            $this->addAccessLocks($link->getAccessLocks());
            $this->shouldHideIfInaccessible($link->shouldHideIfInaccessible());
            $checkUriMatch = $link->shouldCheckMatch();

            $this->addAltMatches(...$link->getAltMatches());

            if ($link->shouldOpenInNewWindow()) {
                $this->getTag()->setAttribute('target', '_blank');
            }

            $this->addClass($link->getClass());
            $this->setDataAttribute('menuid', $link->getId());
        }

        $this->setUri($uri, $checkUriMatch);

        if ($matchRequest !== null) {
            $this->setMatchRequest($matchRequest);
        }

        $this->setBody($body);
    }

    protected function _render()
    {
        if ($this->_uri === null && $this->_body->isEmpty()) {
            return;
        }

        $tag = $this->getTag();
        $url = $this->_context->uri->__invoke($this->_uri);

        if ($url instanceof linkLib\http\IUrl) {
            $request = $url->getDirectoryRequest();
        } else {
            $request = null;
        }

        $body = $this->_body;

        $active = $this->_isActive || $this->_isComputedActive;
        $disabled = $this->_isDisabled;

        if ($this->_uri === null) {
            $disabled = true;
        }

        if ($this->_checkAccess === null) {
            $this->_checkAccess = (bool)$request;

            // Tidy this up :)
            if ($this->_checkAccess && $this->_context->location->isArea('mail')) {
                $this->_checkAccess = false;
            }
        }

        if ($this->_checkAccess && !$disabled) {
            $userManager = $this->_context->user;
            $isLoggedIn = $userManager->isLoggedIn();

            if ($request
            && ($isLoggedIn || $this->_hideIfInaccessible)
            && !$userManager->canAccess($request, null, true)) {
                $disabled = true;
            }

            if (!$disabled) {
                foreach ($this->_accessLocks as $lock) {
                    if (!$userManager->canAccess($lock, null, true)) {
                        $disabled = true;
                        break;
                    }
                }
            }
        }

        if ($disabled && $this->_hideIfInaccessible) {
            return null;
        }

        if (!$disabled) {
            $tag->setAttribute('href', $url);
        }

        if (!empty($this->_rel)) {
            $tag->setAttribute('rel', implode(' ', array_keys($this->_rel)));
        }

        if (!$active && $this->_matchRequest && $this->_isComputedActive !== false) {
            $matchRequest = arch\Request::factory($this->_matchRequest);

            if ($this->_matchExact || $matchRequest->path->isEmpty()) {
                $active = $matchRequest->eq($this->_context->request);
            } else {
                $active = $this->_context->request->matches($matchRequest);
            }
        }

        if (!$active && !empty($this->_altMatches)) {
            foreach ($this->_altMatches as $match) {
                $matchRequest = arch\Request::factory($match);

                if ($this->_context->request->matches($matchRequest)) {
                    $active = true;
                    break;
                }
            }
        }

        $this->_isComputedActive = $active;

        if ($disabled) {
            $tag->addClass('disabled');
        }

        if ($active) {
            $tag->addClass($this->getActiveClass());
        }


        $this->_applyTargetAwareAttributes($tag);


        if ($this->_description) {
            $tag->setTitle($this->_description);
        }


        $icon = $this->_generateIcon();

        if ($this->_hrefLang !== null) {
            $tag->setAttribute('hreflang', $this->_hrefLang);
        }

        if ($this->_media !== null) {
            $tag->setAttribute('media', $this->_media);
        }

        if ($this->_contentType !== null) {
            $tag->setAttribute('type', $this->_contentType);
        }

        if ($this->_disposition !== null) {
            $tag->addClass($this->getDisposition());
        }


        if (!$this->hasBody()) {
            if ($url !== null) {
                $body = clone $url;
            } else {
                $body = $this->_context->uri->__invoke($this->_uri);
            }

            $body = $body->toReadableString();
        }

        if ($this->_note !== null) {
            $body = [
                $body, ' ',
                new aura\html\Element('sup', $this->_note)
            ];
        }

        if ($icon) {
            $tag->addClass('hasIcon');
        }

        return $tag->renderWith([$icon, $body]);
    }




    // Match
    public function shouldMatchExact(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_matchExact = (bool)$flag;
            return $this;
        }

        return $this->_matchExact;
    }


    // Relationship
    public function setRelationship(...$rel)
    {
        $this->_rel = [];
        return $this->addRelationship(...$rel);
    }

    public function addRelationship(...$rel)
    {
        foreach ($rel as $val) {
            $val = strtolower((string)$val);
            $parts = explode(' ', $val);

            foreach ($parts as $part) {
                switch ($part) {
                    case 'alternate':
                    case 'author':
                    case 'bookmark':
                    case 'external':
                    case 'help':
                    case 'license':
                    case 'next':
                    case 'nofollow':
                    case 'noreferrer':
                    case 'prefetch':
                    case 'prev':
                    case 'search':
                    case 'sidebar':
                    case 'tag':
                        $this->_rel[$val] = true;
                        break;
                }
            }
        }

        return $this;
    }

    public function getRelationship()
    {
        return array_keys($this->_rel);
    }

    public function removeRelationship(...$rel)
    {
        foreach ($rel as $val) {
            $val = strtolower((string)$val);
            $parts = explode(' ', $val);

            foreach ($parts as $part) {
                unset($this->_rel[$part]);
            }
        }

        return $this;
    }



    // Active
    public function isActive(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isActive = $flag;
            $this->_isComputedActive = null;

            return $this;
        }

        return $this->_isActive;
    }

    public function setActiveIf($active)
    {
        if ($active !== null) {
            $this->_isActive = (bool)$active;
        }

        return $this;
    }

    public function isComputedActive()
    {
        if ($this->_isComputedActive !== null) {
            return $this->_isComputedActive;
        }

        $active = $this->_isActive;
        $request = $this->_context->request;

        if (!$active && $this->_matchRequest) {
            $matchRequest = $this->_context->uri->directoryRequest($this->_matchRequest);

            if ($this->_matchExact) {
                $active = $matchRequest->eq($request);
            } else {
                $active = $request->matches($matchRequest);
            }
        }

        if (!$active && !empty($this->_altMatches)) {
            foreach ($this->_altMatches as $match) {
                $matchRequest = $this->_context->uri->directoryRequest($match);

                if ($request->matches($matchRequest)) {
                    $active = true;
                    break;
                }
            }
        }

        $this->_isComputedActive = $active;
        return $active;
    }


    public function setActiveClass($class)
    {
        $this->_activeClass = $class;

        if (empty($this->_activeClass)) {
            $this->_activeClass = null;
        }

        return $this;
    }

    public function getActiveClass()
    {
        if (!empty($this->_activeClass)) {
            return $this->_activeClass;
        }

        return static::DEFAULT_ACTIVE_CLASS;
    }


    // Hiding
    public function shouldHideIfInaccessible(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_hideIfInaccessible = $flag;
            return $this;
        }

        return $this->_hideIfInaccessible;
    }

    // Language
    public function setHrefLanguage($language)
    {
        $this->_hrefLang = $language;
        return $this;
    }

    public function getHrefLanguage()
    {
        return $this->_hrefLang;
    }


    // Media
    public function setMedia($media)
    {
        $this->_media = $media;
        return $this;
    }

    public function getMedia()
    {
        return $this->_media;
    }


    // Mime type
    public function setContentType($type)
    {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $lockCount = count($this->_accessLocks) . ' (';

        if (!$this->_checkAccess) {
            $lockCount .= 'not ';
        }

        $lockCount .= 'checked)';

        yield 'properties' => [
            '*uri' => $this->_uri,
            '*matchRequest' => $this->_matchRequest,
            '*rel' => $this->getRelationship(),
            '*isActive' => $this->_isActive,
            '*description' => $this->_description,
            '%accessLocks' => $lockCount,
            '%tag' => $this->getTag()
        ];

        yield 'values' => is_array($this->_body) ?
            $this->_body : $this->_body->toArray();
    }
}
