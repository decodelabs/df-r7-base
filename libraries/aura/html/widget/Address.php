<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\user;

class Address extends Base implements core\IDumpable {
    
    const PRIMARY_TAG = 'div';
    
    const SHORT = 'short';
    const LONG = 'long';
    const FULL = 'full';
    
    protected $_address;
    protected $_mode = self::FULL;
    
    public function __construct(user\IAddress $address) {
        $this->setAddress($address);
    }
    
    public function setAddress(user\IAddress $address) {
        $this->_address = $address;
        return $this;
    }
    
    public function getAddress() {
        return $this->_address;
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $content = new aura\html\ElementContent();
        $view = $this->getRenderTarget()->getView();
        
        $poBox = $this->_address->getPostOfficeBox();
        $streetAddress = $this->_address->getStreetAddress();
        $extendedAddress = $this->_address->getExtendedAddress();
        $locality = $this->_address->getLocality();
        $region = $this->_address->getRegion();
        $postcode = $this->_address->getPostalCode();
        $countryCode = $this->_address->getCountryCode();
        
        $isFull = $this->_mode == self::FULL;
        $isShort = $this->_mode == self::SHORT;
        $blockTag = $isFull ? 'div' : 'span';
        $tag->setName($blockTag);
        
        if(!empty($poBox)) {
            $content->push(new aura\html\Element($blockTag, $poBox, array('class' => 'post-office-box')));
        }
        
        if(!empty($streetAddress)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }
            
            $content->push(new aura\html\Element($blockTag, $streetAddress, array('class' => 'street-address')));
        }
        
        if(!empty($extendedAddress)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }
            
            $content->push(new aura\html\Element($blockTag, $extendedAddress, array('class' => 'extended-address')));
        }
        
        if(!$isShort && !empty($locality)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }
            
            $content->push(new aura\html\Element('span', $locality, array('class' => 'locality')));
        }
        
        if(!$isShort && !empty($region)) {
            if(!empty($locality) || (!$isFull && !$content->isEmpty())) {
                $content->push(', ');
            }
            
            $content->push(new aura\html\Element('span', $region, array('class' => 'region')));
        }
        
        if(!empty($postcode)) {
            if(strlen($region) == 2) {
                $content->push(' ', new aura\html\Element('span', $postcode, array('class' => 'postal-code')));
            } else {
                if(!$isFull && !$content->isEmpty()) {
                    $content->push(', ');
                }
                
                $content->push(new aura\html\Element($blockTag, $postcode, array('class' => 'postal-code')));
            }
        }
        
        if(!empty($countryCode)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }
            
            $content->push(new aura\html\Element(
                $blockTag,
                $isShort ? $countryCode : $view->getContext()->i18n->countries->getName($countryCode),
                array('class' => 'country-name')
            ));
        }
        
        return $tag->renderWith($content);
    }

    public function setMode($mode) {
        switch($mode = strtolower($mode)) {
            case self::SHORT:
            case self::LONG:
            case self::FULL:
                $this->_mode = $mode;
                break;
           
            default:
                $this->_mode = self::FULL;
                break;
        }
        
        return $this;
    }
    
    public function getMode() {
        return $this->_mode;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'mode' => $this->_mode,
            'address' => $this->_address,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
