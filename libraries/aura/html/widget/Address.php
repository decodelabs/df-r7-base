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
use df\arch;

class Address extends Base implements core\IDumpable {

    const PRIMARY_TAG = 'div';

    const SHORT = 'short';
    const LONG = 'long';
    const FULL = 'full';

    protected $_address;
    protected $_mode = self::FULL;
    protected $_shouldShowCountry = true;

    public function __construct(arch\IContext $context, $address=null) {
        parent::__construct($context);
        $this->setAddress($address);
    }

    public function setAddress($address=null) {
        if(is_array($address)) {
            $address = user\PostalAddress::fromArray($address);
        } else if(!$address instanceof user\IPostalAddress) {
            $address = null;
        }

        $this->_address = $address;
        return $this;
    }

    public function getAddress() {
        return $this->_address;
    }

    public function shouldShowCountry($flag=null) {
        if($flag !== null) {
            $this->_shouldShowCountry = (bool)$flag;
            return $this;
        }

        return $this->_shouldShowCountry;
    }

    protected function _render() {
        if($this->_address === null) {
            return null;
        }

        $tag = $this->getTag();
        $content = new aura\html\ElementContent();

        $poBox = $this->_address->getPostOfficeBox();
        $streetAddress = $this->_address->getMainStreetLine();
        $extendedAddress = $this->_address->getExtendedStreetLine();
        $locality = $this->_address->getLocality();
        $region = $this->_address->getRegion();
        $postcode = $this->_address->getPostalCode();
        $countryCode = $this->_address->getCountryCode();

        $isFull = $this->_mode == self::FULL;
        $isShort = $this->_mode == self::SHORT;

        if($isFull) {
            $blockTag = 'div';
        } else {
            $blockTag = 'span';
        }

        $tag->setName($blockTag);

        if(!empty($poBox)) {
            $content->push(new aura\html\Element($blockTag, $poBox, ['class' => 'post-office-box']));
        }

        if(!empty($streetAddress)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $streetAddress, ['class' => 'street-address']));
        }

        if(!empty($extendedAddress)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $extendedAddress, ['class' => 'extended-address']));
        }

        if(!$isShort && !empty($locality)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $locality, ['class' => 'locality']));
        }

        if(!$isShort && !empty($region)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $region, ['class' => 'region']));
        }

        if(!empty($postcode)) {
            if(strlen($region) == 2) {
                $content->push(' ', new aura\html\Element('span', $postcode, ['class' => 'postal-code']));
            } else {
                if(!$isFull && !$content->isEmpty()) {
                    $content->push(', ');
                }

                $content->push(new aura\html\Element($blockTag, $postcode, ['class' => 'postal-code']));
            }
        }

        if($this->_shouldShowCountry && !empty($countryCode)) {
            if(!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            if($isShort) {
                $country = $countryCode;
            } else {
                $country = $this->_context->i18n->countries->getName($countryCode);
            }

            $content->push(new aura\html\Element(
                $blockTag, $country, ['class' => 'country-name']
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
            'tag' => $this->getTag()
        ];
    }
}
