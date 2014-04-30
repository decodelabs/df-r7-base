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

    public function getDefaultValues() {
        return [
            'defaultTransport' => 'Mail',
            'defaultAddress' => 'webmaster@mydomain.com',
            'defaultReturnPath' => null,
            'adminAddresses' => [],
            'catchAllBCC' => [],
            'devmailTesting' => true,
            'transports' => flow\mail\transport\Base::getAllDefaultConfigValues()
        ];
    }

    public function setDefaultTransport($name) {
        if(!flow\mail\transport\Base::isValidTransport($name)) {
            throw new InvalidArgumentException(
                'Transport '.$name.' is not available'
            );
        }

        $this->values->defaultTransport = $name;
        return $this;
    }

    public function getDefaultTransport() {
        return $this->values->get('defaultTransport', 'Mail');
    }

    public function getDefaultTransportSettings($checkName=null) {
        return $this->values->transports->{$this->getDefaultTransport()};
    }

    public function getTransportSettings($name) {
        return $this->values->transports->{$name};
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

    public function setDefaultReturnPath($address) {
        $address = Address::factory($address);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'Return path '.(string)$address.' is invalid'
            );
        }

        $this->values['defaultReturnPath'] = $address->getAddress();
        return $this;
    }

    public function getDefaultReturnPath() {
        return $this->values['defaultReturnPath'];
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
        $output = [];

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
        $output = [];

        foreach($this->values->catchAllBCC as $address) {
            $output[] = Address::factory($address->getValue());
        }

        return $output;
    }

    public function useDevmailInTesting($flag=null) {
        if($flag !== null) {
            $this->values->devmailTesting = (bool)$flag;
            return $this;
        }

        if(!isset($this->values->devmailTesting)) {
            $this->values->devmailTesting = true;
            $this->save();
        }

        return (bool)$this->values['devmailTesting'];
    }
}