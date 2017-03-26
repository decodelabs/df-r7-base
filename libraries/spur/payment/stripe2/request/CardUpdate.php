<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\request;

use df;
use df\core;
use df\spur;
use df\mint;

class CardUpdate implements spur\payment\stripe2\ICardUpdateRequest {

    use TRequest_Metadata;

/*
    id
    ?address_city
    ?address_country
    ?address_line1
    ?address_line2
    ?address_state
    ?address_zip
    ?exp_month
    ?exp_year
    ?metadata
    ?name
*/

    protected $_customerId;
    protected $_cardId;

    protected $_streetLine1;
    protected $_streetLine2;
    protected $_locality;
    protected $_region;
    protected $_postalCode;
    protected $_country;

    protected $_expiryMonth;
    protected $_expiryYear;

    protected $_name;

    public function __construct(string $customerId, string $cardId) {
        $this->setCustomerId($customerId);
        $this->setCardId($cardId);
    }

    public function setCustomerId(string $customerId) {
        $this->_customerId = $customerId;
        return $this;
    }

    public function getCustomerId(): string {
        return $this->_customerId;
    }

    public function setCardId(string $cardId) {
        $this->_cardId = $cardId;
        return $this;
    }

    public function getCardId(): string {
        return $this->_cardId;
    }


    public function setStreetLine1(?string $line1) {
        $this->_streetLine1 = $line1;
        return $this;
    }

    public function getStreetLine1(): ?string {
        return $this->_streetLine1;
    }

    public function setStreetLine2(?string $line2) {
        $this->_streetLine2 = $line2;
        return $this;
    }

    public function getStreetLine2(): ?string {
        return $this->_streetLine2;
    }

    public function setLocality(?string $locality) {
        $this->_locality = $locality;
        return $this;
    }

    public function getLocality(): ?string {
        return $this->_locality;
    }

    public function setRegion(?string $region) {
        $this->_region = $region;
        return $this;
    }

    public function getRegion(): ?string {
        return $this->_region;
    }

    public function setPostalCode(?string $code) {
        $this->_postalCode = $code;
        return $this;
    }

    public function getPostalCode(): ?string {
        return $this->_postalCode;
    }

    public function setCountry(?string $country) {
        $this->_country = $country;
        return $this;
    }

    public function getCountry(): ?string {
        return $this->_country;
    }


    public function setExpiryMonth(?int $month) {
        $this->_expiryMonth = $month;
        return $this;
    }

    public function getExpiryMonth(): ?int {
        return $this->_expiryMonth;
    }

    public function setExpiryYear(?int $year) {
        $this->_expiryYear = $year;
        return $this;
    }

    public function getExpiryYear(): ?int {
        return $this->_expiryYear;
    }


    public function setName(?string $name) {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string {
        return $this->_name;
    }



    public function toArray(): array {
        $output = [];

        if($this->_streetLine1 !== null) {
            $output['address_line1'] = $this->_streetLine1;
        }
        if($this->_streetLine2 !== null) {
            $output['address_line2'] = $this->_streetLine2;
        }
        if($this->_locality !== null) {
            $output['address_city'] = $this->_locality;
        }
        if($this->_region !== null) {
            $output['address_state'] = $this->_region;
        }
        if($this->_postalCode !== null) {
            $output['address_zip'] = $this->_postalCode;
        }
        if($this->_country !== null) {
            $output['address_country'] = $this->_country;
        }

        if($this->_expiryMonth !== null) {
            $output['exp_month'] = $this->_expiryMonth;
        }
        if($this->_expiryYear !== null) {
            $output['exp_year'] = $this->_expiryYear;
        }

        if($this->_name !== null) {
            $output['name'] = $this->_name;
        }

        $this->_applyMetadata($output);

        return $output;
    }
}