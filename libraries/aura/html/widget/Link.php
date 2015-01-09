<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\arch;
use df\aura;
use df\link as linkLib;
use df\user;

class Link extends Base implements ILinkWidget, IDescriptionAwareLinkWidget, IIconProviderWidget, core\IDumpable {
    
    use TWidget_BodyContentAware;
    use TWidget_Disableable;
    use TWidget_TargetAware;
    use TWidget_DispositionAware;
    use TWidget_IconProvider;
    use arch\navigation\TSharedLinkComponents;
       
    const PRIMARY_TAG = 'a';
    const WRAP_BODY = true;
    const DEFAULT_ACTIVE_CLASS = 'active';
    
    protected $_rel = [];
    protected $_isActive = false;
    protected $_isComputedActive = null;
    protected $_hrefLang;
    protected $_media;
    protected $_contentType;
    protected $_bodyWrapper;
    protected $_activeClass;
    protected $_shouldWrapBody = true;
    
    public function __construct(arch\IContext $context, $uri, $body=null, $matchRequest=null) {
        $checkUriMatch = false;
        $this->_checkAccess = null;
        
        if($matchRequest === true) {
            $checkUriMatch = true;
            $matchRequest = null;
        }

        if($uri instanceof arch\navigation\entry\Link) {
            $link = $uri;
            $uri = $link->getUri();
            $body = $link->getBody();

            if($icon = $link->getIcon()) {
                $this->setIcon($icon);
            }

            if($description = $link->getDescription()) {
                $this->setDescription($description);
            }

            if(null !== ($note = $link->getNote())) {
                $this->setNote($note);
            }

            if($disposition = $link->getDisposition()) {
                $this->setDisposition($disposition);
            }

            $this->addAccessLocks($link->getAccessLocks());
            $this->shouldHideIfInaccessible($link->shouldHideIfInaccessible());
            $checkUriMatch = $link->shouldCheckMatch();

            $this->addAltMatches($link->getAltMatches());

            if($link->shouldOpenInNewWindow()) {
                $this->getTag()->setAttribute('target', '_blank');
            }

            $this->addClass($link->getClass());
            $this->setDataAttribute('menuid', $link->getId());
        }
        
        $this->setUri($uri, $checkUriMatch);
        
        if($matchRequest !== null) {
            $this->setMatchRequest($matchRequest);
        }
        
        $this->setBody($body);

        if(static::WRAP_BODY) {
            $this->_bodyWrapper = new aura\html\Tag('span', ['class' => 'body']);
        }
    }
    
    protected function _render() {
        $view = $this->getRenderTarget()->getView();
        $context = $view->getContext();

        $tag = $this->getTag();
        $url = $view->uri->__invoke($this->_uri);

        if($url instanceof link\http\IUrl) {
            $request = $url->getDirectoryRequest();
        } else {
            $request = null;
        }
        
        $body = $this->_body;
        
        $active = $this->_isActive || $this->_isComputedActive;
        $disabled = $this->_isDisabled;

        if($this->_uri === null) {
            $disabled = true;
        }

        if($this->_checkAccess === null) {
            $this->_checkAccess = (bool)$request;
        }

        if($this->_checkAccess && !$disabled) {
            $userManager = $context->user;

            if($request && !$userManager->canAccess($request, null, true)) {
                $disabled = true;
            }
            
            if(!$disabled) {
                foreach($this->_accessLocks as $lock) {
                    if(!$userManager->canAccess($lock, null, true)) {
                        $disabled = true;
                        break;
                    }
                }
            }
        }

        if($disabled && $this->_hideIfInaccessible) {
            return null;
        }
        
        if(!$disabled) {
            $tag->setAttribute('href', $url);
        }
        
        if(!empty($this->_rel)) {
            $tag->setAttribute('rel', implode(' ', array_keys($this->_rel)));
        }
        
        if(!$active && $this->_matchRequest && $this->_isComputedActive !== false) {
            $matchRequest = arch\Request::factory($this->_matchRequest);
            $active = $matchRequest->matches($context->request);
        }

        if(!$active && !empty($this->_altMatches)) {
            foreach($this->_altMatches as $match) {
                $matchRequest = arch\Request::factory($match);

                if($matchRequest->contains($context->request)) {
                    $active = true;
                    break;
                }
            }
        }

        $this->_isComputedActive = $active;
        
        if($disabled) {
            $tag->addClass('disabled');
        }
        
        if($active) {
            $tag->addClass($this->getActiveClass());
        }
        
        
        $this->_applyTargetAwareAttributes($tag);
        

        if($this->_description) {
            $tag->setTitle($this->_description);
        }

        
        $icon = $this->_generateIcon();
        
        if($this->_hrefLang !== null) {
            $tag->setAttribute('hreflang', $this->_hrefLang);
        }
        
        if($this->_media !== null) {
            $tag->setAttribute('media', $this->_media);
        }
        
        if($this->_contentType !== null) {
            $tag->setAttribute('type', $this->_contentType);
        }

        if($this->_disposition !== null) {
            $tag->addClass($this->getDisposition());
        }
        
        
        if(!$this->hasBody()) {
            if($url !== null) {
                $body = $url;
            } else {
                $body = $view->uri->__invoke($this->_uri);
            }
        }

        if($this->_note !== null) {
            $body = [
                $body,
                new aura\html\Element('sup', $this->_note)
            ];
        }
        
        if(static::WRAP_BODY && $this->_shouldWrapBody) {
            $body = $this->_bodyWrapper->renderWith($body);
        }

        if($icon) {
            $tag->addClass('hasIcon');
        }

        return $tag->renderWith([$icon, $body]);
    }
    
    
// Relationship
    public function setRelationship($rel) {
        $this->_rel = [];
        return $this->addRelationship($rel);
    }
    
    public function addRelationship($rel) {
        if(!is_array($rel)) {
            $rel = func_get_args();
        }
        
        foreach($rel as $val) {
            $val = strtolower($val);
            $parts = explode(' ', $val);
            
            foreach($parts as $part) {
                switch($part) {
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
    
    public function getRelationship() {
        return array_keys($this->_rel);
    }
    
    public function removeRelationship($rel) {
        if(!is_array($rel)) {
            $rel = func_get_args();
        }
        
        foreach($rel as $val) {
            $val = strtolower($val);
            $parts = explode(' ', $val);
            
            foreach($parts as $part) {
                unset($this->_rel[$part]);
            }
        }
        
        return $this;
    }
    

    
// Active
    public function isActive($flag=null) {
        if($flag !== null) {
            $this->_isActive = (bool)$flag;
            $this->_isComputedActive = null;

            return $this;
        }
        
        return $this->_isActive;
    }

    public function setActiveIf($active) {
        if($active !== null) {
            $this->_isActive = (bool)$active;
        }

        return $this;
    }

    public function isComputedActive() {
        if($this->_isComputedActive !== null) {
            return $this->_isComputedActive;
        }

        try {
            $view = $this->getRenderTarget()->getView();
            $context = $view->getContext();
        } catch(\Exception $e) {
            return false;
        }

        $active = $this->_isActive;
        $request = $context->request;
        
        if(!$active && $this->_matchRequest) {
            $matchRequest = $context->uri->directoryRequest($this->_matchRequest);
            $active = $matchRequest->matches($request);
        }

        if(!$active && !empty($this->_altMatches)) {
            foreach($this->_altMatches as $match) {
                $matchRequest = $context->uri->directoryRequest($match);

                if($matchRequest->contains($request)) {
                    $active = true;
                    break;
                }
            }
        }

        $this->_isComputedActive = $active;
        return $active;
    }
    

    public function setActiveClass($class) {
        $this->_activeClass = $class;

        if(empty($this->_activeClass)) {
            $this->_activeClass = null;
        }

        return $this;
    }

    public function getActiveClass() {
        if(!empty($this->_activeClass)) {
            return $this->_activeClass;
        }

        return static::DEFAULT_ACTIVE_CLASS;
    }


// Hiding
    public function shouldHideIfInaccessible($flag=null) {
        if($flag !== null) {
            $this->_hideIfInaccessible = (bool)$flag;
            return $this;
        }

        return $this->_hideIfInaccessible;
    }
    
// Language
    public function setHrefLanguage($language) {
        $this->_hrefLang = $language;
        return $this;
    }
    
    public function getHrefLanguage() {
        return $this->_hrefLang;
    }
    
    
// Media
    public function setMedia($media) {
        $this->_media = $media;
        return $this;
    }
    
    public function getMedia() {
        return $this->_media;
    }
    
    
// Mime type
    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    } 
    
    public function getContentType() {
        return $this->_contentType;
    }


// Body wrapper
    public function getBodyWrapperTag() {
        if(!static::WRAP_BODY) {
            throw new LogicException(
                'This type of link widget does not support body wrappers'
            );
        }

        return $this->_bodyWrapper;
    }

    public function shouldWrapBody($flag=null) {
        if($flag !== null) {
            $this->_shouldWrapBody = (bool)$flag;
            return $this;
        }

        return $this->_shouldWrapBody;
    }
    
    
// Dump
    public function getDumpProperties() {
        $lockCount = count($this->_accessLocks).' (';
        
        if(!$this->_checkAccess) { 
            $lockCount .= 'not ';
        }
        
        $lockCount .= 'checked)';
        
        return [
            'uri' => $this->_uri,
            'matchRequest' => $this->_matchRequest,
            'rel' => $this->getRelationship(),
            'isActive' => $this->_isActive,
            'tag' => $this->getTag(),
            'body' => $this->_body,
            'description' => $this->_description,
            'accessLocks' => $lockCount,
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}