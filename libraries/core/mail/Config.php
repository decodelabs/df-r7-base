<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail;

use df;
use df\core;
    
class Config extends core\Config {

    const ID = 'mail';
    const USE_ENVIRONMENT_ID_BY_DEFAULT = true;

    public function getDefaultValues() {
        return [
            'defaultTransport' => 'Mail',
            'defaultAddress' => 'webmaster@mydomain.com',
            'catchAllBCC' => []
        ];
    }

    public function setDefaultTransport($name) {
        if(!core\mail\transport\Base::isValidTransport($name)) {
            throw new InvalidArgumentException(
                'Transport '.$name.' is not available'
            );
        }

        $this->_values['defaultTransport'] = $name;
        return $this;
    }

    public function getDefaultTransport() {
        if(isset($this->_values['defaultTransport'])) {
            return $this->_values['defaultTransport'];
        }

        return 'Mail';
    }

    public function setDefaultAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'Email address '.(string)$address.' is invalid'
            );
        }

        $this->_values['defaultAddress'] = (string)$address;
        return $this;
    }

    public function getDefaultAddress() {
        if(isset($this->_values['defaultAddress'])) {
            return $this->_values['defaultAddress'];
        }

        return 'webmaster@mydomain.com';
    }

    public function setCatchAllBCCAddresses(array $addresses) {
        foreach($addresses as $i => $address) {
            $address = Address::factory($address);

            if($address->isValid()) {
                $addresses[$i] = (string)$address;
            }
        }

        $this->_values['catchAllBCC'] = $addresses;
        return $this;
    }

    public function getCatchAllBCCAddresses() {
        $output = array();

        if(isset($this->_values['catchAllBCC'])) {
            foreach($this->_values['catchAllBCC'] as $address) {
                $output[] = Address::factory($address);
            }
        }

        return $output;
    }
}