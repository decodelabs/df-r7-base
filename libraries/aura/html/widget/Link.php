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

class Link extends Base implements ILinkWidget, core\IDumpable {
    
    use TBodyContentAwareWidget;
    use TDisableableWidget;
    use TAccessControlledWidget;
       
    const PRIMARY_TAG = 'a';
    
    protected $_uri;
    protected $_matchRequest;
    protected $_rel = array();
    protected $_isActive = false;
    
    public function __construct($uri, $body=null, $matchRequest=null) {
        $checkUriMatch = false;
        
        if($matchRequest === true) {
            $checkUriMatch = true;
            $matchRequest = null;
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
        
        $active = $this->_isActive;
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
        
        if(!$disabled) {
            $tag->setAttribute('href', $url = $context->normalizeOutputUrl($this->_uri));
        }
        
        if(!empty($this->_rel)) {
            $tag->setAttribute('rel', implode(' ', array_keys($this->_rel)));
        }
        
        if(!$active && $this->_matchRequest) {
            $matchRequest = arch\Request::factory($this->_matchRequest);
            $active = $matchRequest->eq($context->request);
        }
        
        if($active) {
            $tag->addClass('state-active');
        }
        
        if($body->isEmpty()) {
            if($url !== null) {
                $body = $url;
            } else {
                $body = $context->normalizeOutputUrl($this->_uri);
            }
        }
        
        return $tag->renderWith($body);
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
    
    
    
// Target
    public function setTarget($target) {
        $this->getTag()->setAttribute('target', $target);
        return $this;
    }
    
    public function getTarget() {
        return $this->getTag()->getAttribute('target');
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
            return $this;
        }
        
        return $this->_isActive;
    }
    
    
// Language
    public function setHrefLanguage($language) {
        $this->getTag()->setAttribute('hreflang', $language);
        return $this;
    }
    
    public function getHrefLanguage() {
        return $this->getTag()->getAttribute('hreflang');
    }
    
    
// Media
    public function setMedia($media) {
        $this->getTag()->setAttribute('media', $media);
        return $this;
    }
    
    public function getMedia() {
        return $this->getTag()->getAttribute('media');
    }
    
    
// Mime type
    public function setContentType($type) {
        $this->getTag()->setAttribute('type', $type);
        return $this;
    } 
    
    public function getContentType() {
        return $this->getTag()->getAttribute('type');
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'uri' => $this->_uri,
            'matchRequest' => $this->_matchRequest,
            'rel' => $this->getRelationship(),
            'isActive' => $this->_isActive,
            'tag' => $this->getTag(),
            'body' => $this->_body,
            'accessLocks' => count($this->_accessLocks).' ('.($this->_checkAccess ? '' : 'not ').'checked)',
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}