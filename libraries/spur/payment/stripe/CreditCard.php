<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\mint;
use df\user;

class CreditCard extends mint\CreditCard implements ICreditCard {

    use TMediatorProvider;

    protected $_id;
    protected $_fingerprint;
    protected $_verificationCheckResult = true;
    protected $_addressCheckResult = true;
    protected $_postalCodeCheckResult = true;

    public function __construct(IMediator $mediator, core\collection\ITree $data) {
        $this->_mediator = $mediator;

        $this->_id = $data['id'];
        $this->_name = $data['name'];
        $this->_last4 = $data['last4'];
        $this->_expiryMonth = (int)$data['exp_month'];
        $this->_expiryYear = (int)$data['exp_year'];

        $isCountryCode = strlen($data['address_country']) == 2;

        $this->_billingAddress = user\PostalAddress::fromArray([
            'street1' => $data['address_line1'],
            'street2' => $data['address_line2'],
            'locality' => $data['address_city'],
            'region' => $data['address_state'],
            'postalCode' => $data['address_zip'],
            'countryCode' => $isCountryCode ? $data['address_country'] : null,
            'countryName' => $isCountryCode ? null : $data['address_country']
        ]);

        $this->_fingerprint = $data['fingerprint'];
        $this->_verificationCheckResult = $data->has('cvc_check') ? (bool)$data['cvc_check'] : true;
        $this->_addressCheckResult = $data->has('address_line1_check') ? (bool)$data['address_line1_check'] : true;
        $this->_postalCodeCheckResult = $data->has('address_zip_check') ? (bool)$data['address_zip_check'] : true;
    }

    public function getId() {
        return $this->_id;
    }

    public function getFingerprint() {
        return $this->_fingerprint;
    }

    public function hasPassedVerificationCheck() {
        return $this->_verificationCheckResult;
    }

    public function hasPassedAddressCheck() {
        return $this->_addressCheckResult;
    }

    public function hasPassedPostalCodeCheck() {
        return $this->_postalCodeCheckResult;
    }


// Dump
    public function getDumpProperties() {
        return array_merge(
            ['id' => $this->_id],
            parent::getDumpProperties(),
            [
                'fingerprint' => $this->_fingerprint,
                'verificationCheck' => $this->_verificationCheckResult ? 'pass' : 'fail',
                'addressCheck' => $this->_addressCheckResult ? 'pass' : 'fail',
                'postalCodeCheck' => $this->_postalCodeCheckResult ? 'pass' : 'fail'
            ]
        );
    }
}