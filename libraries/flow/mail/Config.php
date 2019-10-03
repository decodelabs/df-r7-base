<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;
use df\halo;
use df\link;

class Config extends core\Config
{
    const ID = 'mail';

    public function getDefaultValues(): array
    {
        return [
            'defaultTransport' => 'Mail',
            'defaultAddress' => $this->_getDefaultAdminAddress(),
            'defaultReturnPath' => null,
            'adminAddresses' => [],
            'devAddresses' => [],
            'captureInTesting' => true,
            'transports' => flow\mail\transport\Base::getAllDefaultConfigValues(),
            'listSources' => [
                '!example' => [
                    'adapter' => 'Mailchimp3',
                    'apiKey' => null
                ]
            ]
        ];
    }


    // Transport
    public function setDefaultTransport($name)
    {
        if (!flow\mail\transport\Base::isValidTransport($name)) {
            throw new InvalidArgumentException(
                'Transport '.$name.' is not available'
            );
        }

        $this->values->defaultTransport = $name;
        return $this;
    }

    public function getDefaultTransport()
    {
        return $this->values->get('defaultTransport', 'Mail');
    }

    public function getDefaultTransportSettings($checkName=null)
    {
        return $this->values->transports->{$this->getDefaultTransport()};
    }

    public function getTransportSettings($name)
    {
        return $this->values->transports->{$name};
    }


    // Default addresses
    public function setDefaultAddress($address, $name=null)
    {
        $address = Address::factory($address, $name);

        if (!$address->isValid()) {
            throw new InvalidArgumentException(
                'Email address '.(string)$address.' is invalid'
            );
        }

        $this->values['defaultAddress'] = (string)$address;
        return $this;
    }

    public function getDefaultAddress()
    {
        $output = $this->values->get('defaultAddress');

        if ($output === null) {
            $output = $this->_getDefaultAdminAddress();
        }

        return $output;
    }

    public function setDefaultReturnPath($address)
    {
        $address = Address::factory($address);

        if (!$address->isValid()) {
            throw new InvalidArgumentException(
                'Return path '.(string)$address.' is invalid'
            );
        }

        $this->values['defaultReturnPath'] = $address->getAddress();
        return $this;
    }

    public function getDefaultReturnPath()
    {
        return $this->values['defaultReturnPath'];
    }


    // Admin addresses
    public function setAdminAddresses(array $addresses)
    {
        $values = [];

        foreach ($addresses as $i => $address) {
            $address = Address::factory($address);

            if ($address->isValid()) {
                $values[] = (string)$address;
            }
        }

        $this->values['adminAddresses'] = $values;
        return $this;
    }

    public function getAdminAddresses()
    {
        $output = [];

        foreach ($this->values->adminAddresses as $address) {
            $output[] = Address::factory($address->getValue());
        }

        if (empty($output)) {
            $output[] = Address::factory($this->getDefaultAddress());
        }

        return $output;
    }

    protected function _getDefaultAdminAddress()
    {
        $name = Systemic::$process->getCurrent()->getOwnerName();
        $rootUrl = core\app\runner\http\Config::getInstance()->getRootUrl();

        if ($rootUrl) {
            $domain = (new link\http\Url($rootUrl))->getDomain();
        } else {
            $domain = gethostname();
        }

        return $name.'@'.$domain;
    }


    // Dev addresses
    public function setDevAddresses(array $addresses)
    {
        $values = [];

        foreach ($addresses as $i => $address) {
            $address = Address::factory($address);

            if ($address->isValid()) {
                $values[] = (string)$address;
            }
        }

        $this->values['devAddresses'] = $values;
        return $this;
    }

    public function getDevAddresses()
    {
        $output = [];

        foreach ($this->values->devAddresses as $address) {
            $output[] = Address::factory($address->getValue());
        }

        if (empty($output)) {
            $output[] = Address::factory($this->getDefaultAddress());
        }

        return $output;
    }


    // Capture
    public function shouldCaptureInTesting(bool $flag=null)
    {
        if ($flag !== null) {
            $this->values->captureInTesting = $flag;
            return $this;
        }

        if (!isset($this->values['captureInTesting'])) {
            $this->values->captureInTesting = true;
            $this->save();
        }

        return (bool)$this->values['captureInTesting'];
    }


    // Lists
    public function getListSources()
    {
        $output = [];

        foreach ($this->values->listSources as $key => $node) {
            if (substr($key, 0, 1) == '!') {
                continue;
            }

            $output[$key] = clone $node;
        }

        return $output;
    }

    public function getListSource($id)
    {
        return clone $this->values->listSources->{$id};
    }
}
