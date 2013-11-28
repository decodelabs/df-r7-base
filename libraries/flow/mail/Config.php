<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;
    
class Config extends core\Config {

    const ID = 'mail';
    const USE_TREE = true;    
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues() {
        return [
            'defaultTransport' => [
                'name' => 'Mail'
            ],
            'defaultAddress' => 'webmaster@mydomain.com',
            'adminAddresses' => [],
            'catchAllBCC' => []
        ];
    }

    public function setDefaultTransport($name) {
        if(!flow\mail\transport\Base::isValidTransport($name)) {
            throw new InvalidArgumentException(
                'Transport '.$name.' is not available'
            );
        }

        $this->values['defaultTransport'] = $name;
        return $this;
    }

    public function getDefaultTransport() {
        if($this->values->defaultTransport->hasValue()) {
            return $this->values['defaultTransport'];
        }

        if(isset($this->values->defaultTransport->name)) {
            return $this->values->defaultTransport['name'];
        }

        return 'Mail';
    }

    public function getDefaultTransportSettings($checkName=null) {
        return $this->values->defaultTransport;
    }

    public function setDefaultAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'Email address '.(string)$address.' is invalid'
            );
        }

        $this->values['defaultAddress'] = (string)$address;
        return $this;
    }

    public function getDefaultAddress() {
        return $this->values->get('defaultAddress', 'webmaster@mydomain.com');
    }

    public function setAdminAddresses(array $addresses) {
        $values = [];

        foreach($addresses as $i => $address) {
            $address = Address::factory($address);

            if($address->isValid()) {
                $values[] = (string)$address;
            }
        }

        $this->values['adminAddresses'] = $values;
        return $this;
    }

    public function getAdminAddresses() {
        $output = array();

        foreach($this->values->adminAddresses as $address) {
            $output[] = Address::factory($address->getValue());
        }

        return $output;
    }

    public function setCatchAllBCCAddresses(array $addresses) {
        $values = [];

        foreach($addresses as $i => $address) {
            $address = Address::factory($address);

            if($address->isValid()) {
                $values[] = (string)$address;
            }
        }

        $this->values['catchAllBCC'] = $values;
        return $this;
    }

    public function getCatchAllBCCAddresses() {
        $output = array();

        foreach($this->values->catchAllBCC as $address) {
            $output[] = Address::factory($address->getValue());
        }

        return $output;
    }
}