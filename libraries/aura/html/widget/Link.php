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
use df\user;

class Link extends Base implements ILinkWidget, IIconProviderWidget, core\IDumpable {
    
    use TWidget_BodyContentAware;
    use TWidget_Disableable;
    use TWidget_AccessControlled;
    use TWidget_TargetAware;
    use TWidget_DispositionAware;
    use TWidget_IconProvider;
       
    const PRIMARY_TAG = 'a';
    const WRAP_BODY = true;
    
    protected $_uri;
    protected $_matchRequest;
    protected $_altMatches = array();
    protected $_rel = array();
    protected $_isActive = false;
    protected $_isComputedActive = null;
    protected $_hideIfInaccessible = false;
    protected $_hrefLang;
    protected $_media;
    protected $_contentType;
    protected $_description;
    
    public function __construct(arch\IContext $context, $uri, $body=null, $matchRequest=null) {
        $checkUriMatch = false;
        $this->_checkAccess = true;
        
        if($matchRequest === true) {
            $checkUriMatch = true;
            $matchRequest = null;
        }

        if($uri instanceof arch\navigation\entry\Link) {
            $link = $uri;
            $uri = $link->getLocation();
            $body = $context->_($link->getText());

            if($icon = $link->getIcon()) {
                $this->setIcon($icon);
            }

            if($description = $context->_($link->getDescription())) {
                $this->setDescription($description);
            }

            $this->addAccessLocks($link->getAccessLocks());
            $this->shouldHideIfInaccessible($link->shouldHideIfInaccessible());
            $checkUriMatch = $link->shouldCheckMatch();

            $this->addAltMatches($link->getAltMatches());

            if($link->shouldOpenInNewWindow()) {
                $this->getTag()->setAttribute('target', '_blank');
            }
        }
        
        $this->setUri($uri, $checkUriMatch);
        
        if($matchRequest !== null) {
            $this->setMatchRequest($matchRequest);
        }
        
        $this->setBody($body);
    }
    
    protected function _render() {
        $view = $this->getRenderTarget()->getView();
        $context = $view->getContext();

        $tag = $this->getTag();
        $url = null;
        $body = $this->_body;
        
        $active = $this->_isActive || $this->_isComputedActive;
        $disabled = $this->_isDisabled;
        
        if($this->_checkAccess && !$disabled) {
            $user = $context->user->client;
            
            if(($uri = $context->normalizeOutputUrl($this->_uri, true)) instanceof user\IAccessLock) {
                if(!$user->canAccess($uri)) {
                    $disabled = true;
                }
            }
            
            if(!$disabled) {
                foreach($this->_accessLocks as $lock) {
                    if(!$user->canAccess($lock)) {
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
            $tag->setAttribute('href', $url = $context->normalizeOutputUrl($this->_uri));
        }
        
        if(!empty($this->_rel)) {
            $tag->setAttribute('rel', implode(' ', array_keys($this->_rel)));
        }
        
        if(!$active && $this->_matchRequest && $this->_isComputedActive !== null) {
            $matchRequest = arch\Request::factory($this->_matchRequest);
            $active = $matchRequest->eq($context->request);
        }

        if(!$active && !empty($this->_altMatches)) {
            foreach($this->_altMatches as $match) {
                $matchRequest = arch\Request::factory($match);

                if($matchRequest->eq($context->request)) {
                    $active = true;
                    break;
                }
            }
        }

        $this->_isComputedActive = $active;
        
        if($active) {
            $tag->addClass('state-active');
        }
        
        
        $this->_applyTargetAwareAttributes($tag);
        

        if($this->_description) {
            $tag->setAttribute('title', $this->_description);
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
            $tag->addClass('disposition-'.$this->getDisposition());
        }
        
        
        if($body->isEmpty()) {
            if($url !== null) {
                $body = $url;
            } else {
                $body = $context->normalizeOutputUrl($this->_uri);
            }
        }
        
        if(static::WRAP_BODY) {
            $body = new aura\html\Element('span', $body, ['class' => 'body']);
        }

        if($icon) {
            $tag->addClass('hasIcon');
        }

        return $tag->renderWith([$icon, $body]);
    }
    
    
// Uri
    public function setUri($uri, $setAsMatchRequest=false) {
        $this->_uri = $uri;
        
        if($setAsMatchRequest) {
            $this->setMatchRequest($uri);
        }
        
        return $this;
    }
    
    public function getUri() {
        return $this->_uri;
    }
    
    
// Match request
    public function setMatchRequest($request) {
        $this->_matchRequest = $request;
        return $this;
    }
    
    public function getMatchRequest() {
        return $this->_matchRequest;
    }


    public function addAltMatches($matches) {
        if(!is_array($matches)) {
            $matches = func_get_args();
        }
        
        foreach($matches as $match) {
            $this->addAltMatch($match);
        }
        
        return $this;
    }
    
    public function addAltMatch($match) {
        $match = trim($match);
        
        if(strlen($match)) {
            $this->_altMatches[] = $match;
        }
        
        return $this;
    }
    
    public function getAltMatches() {
        return $this->_altMatches;
    }

    public function clearAltMatches() {
        $this->_altMatches = array();
        return $this;
    }
    
    
// Relationship
    public function setRelationship($rel) {
        $this->_rel = array();
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

// Description
    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
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