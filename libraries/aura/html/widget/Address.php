<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;
use df\aura;

use df\user;

class Address extends Base implements Dumpable
{
    public const PRIMARY_TAG = 'div.address';

    public const SHORT = 'short';
    public const LONG = 'long';
    public const FULL = 'full';

    protected $_address;
    protected $_mode = self::FULL;
    protected $_shouldShowCountry = true;

    public function __construct(arch\IContext $context, $address = null)
    {
        parent::__construct($context);
        $this->setAddress($address);
    }

    public function setAddress($address = null)
    {
        if (is_array($address)) {
            $address = user\PostalAddress::fromArray($address);
        } elseif (!$address instanceof user\IPostalAddress) {
            $address = null;
        }

        $this->_address = $address;
        return $this;
    }

    public function getAddress(): ?user\IPostalAddress
    {
        return $this->_address;
    }

    public function shouldShowCountry(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldShowCountry = $flag;
            return $this;
        }

        return $this->_shouldShowCountry;
    }

    protected function _render()
    {
        if ($this->_address === null) {
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

        if ($isFull) {
            $blockTag = 'div';
        } else {
            $blockTag = 'span';
        }

        $tag->setName($blockTag);

        if (!empty($poBox)) {
            $content->push(new aura\html\Element($blockTag, $poBox, ['class' => 'po-box']));
        }

        if (!empty($streetAddress)) {
            if (!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $streetAddress, ['class' => 'street']));
        }

        if (!empty($extendedAddress)) {
            if (!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $extendedAddress, ['class' => 'extended']));
        }

        if (!$isShort && !empty($locality)) {
            if (!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $locality, ['class' => 'locality']));
        }

        if (!$isShort && !empty($region)) {
            if (!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            $content->push(new aura\html\Element($blockTag, $region, ['class' => 'region']));
        }

        if (!empty($postcode)) {
            if (strlen((string)$region) == 2) {
                $content->push(' ', new aura\html\Element('span', $postcode, ['class' => 'postcode']));
            } else {
                if (!$isFull && !$content->isEmpty()) {
                    $content->push(', ');
                }

                $content->push(new aura\html\Element($blockTag, $postcode, ['class' => 'postcode']));
            }
        }

        if ($this->_shouldShowCountry && !empty($countryCode)) {
            if (!$isFull && !$content->isEmpty()) {
                $content->push(', ');
            }

            if ($isShort) {
                $country = $countryCode;
            } else {
                $country = $this->_context->i18n->countries->getName($countryCode);
            }

            $content->push(new aura\html\Element(
                $blockTag,
                $country,
                ['class' => 'country']
            ));
        }

        return $tag->renderWith($content);
    }

    public function setMode(?string $mode)
    {
        switch ($mode = strtolower((string)$mode)) {
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

    public function getMode(): string
    {
        return $this->_mode;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*mode' => $this->_mode,
            '%tag' => $this->getTag()
        ];

        yield 'value' => $this->_address;
    }
}
