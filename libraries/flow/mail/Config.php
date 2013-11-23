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
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues() {
        return [
            'defaultTransport' => 'Mail',
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
        if(!isset($this->values['defaultTransport'])) {
            $output = 'Mail';
        } else {
            $output = $this->values['defaultTransport'];
        }

        if(is_array($output)) {
            if(isset($output['name'])) {
                return $output['name'];
            }

            $output = 'Mail';
        }

        return $output;
    }

    public function getDefaultTransportSettings($checkName=null) {
        if(isset($this->values['defaultTransport'])
        && is_array($this->values['defaultTransport'])) {
            if($checkName !== null
            && isset($this->values['defaultTransport']['name'])
            && $checkName != $this->values['defaultTransport']['name']) {
                return array();
            }
            
            return $this->values['defaultTransport'];
        }

        return array();
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
        if(isset($this->values['defaultAddress'])) {
            return $this->values['defaultAddress'];
        }

        return 'webmaster@mydomain.com';
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

        if(isset($this->values['adminAddresses'])) {
            foreach($this->values['adminAddresses'] as $address) {
                $output[] = Address::factory($address);
            }
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

        if(isset($this->values['catchAllBCC'])) {
            foreach($this->values['catchAllBCC'] as $address) {
                $output[] = Address::factory($address);
            }
        }

        return $output;
    }
}